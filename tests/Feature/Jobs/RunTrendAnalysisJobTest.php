<?php

use App\Jobs\RunTrendAnalysisJob;
use App\Models\AiSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadTrendAnalysis;
use App\Notifications\TrendAnalysisFailedNotification;
use App\Services\Intelligence\TrendResearcher;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches RunTrendAnalysisJob', function (): void {
    Queue::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    RunTrendAnalysisJob::dispatch($lead, $admin->id);

    Queue::assertPushed(RunTrendAnalysisJob::class);
});

it('creates a completed trend analysis with raw data and AI result', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create([
        'provider' => 'openrouter',
        'model' => 'meta-llama/llama-3.1-8b-instruct:free',
        'temperature' => 0.3,
        'max_tokens' => 2000,
    ]);

    $lead = Lead::factory()->create([
        'title' => 'Sunset Restaurant',
        'category' => 'Restaurant',
    ]);

    $fakeRawData = [
        'reddit' => [['title' => 'Restaurant trends 2025', 'subreddit' => 'restaurants', 'score' => 100, 'comments' => 25, 'url' => 'https://reddit.com/r/restaurants/1', 'created_at' => '2025-03-01']],
        'hackernews' => [['title' => 'Modern restaurant tech', 'url' => 'https://news.ycombinator.com/item?id=1', 'points' => 50, 'comments' => 10, 'created_at' => '2025-03-15']],
        'polymarket' => [],
        'meta' => ['topic' => 'Sunset Restaurant Restaurant', 'days' => 30, 'fetched_at' => now()->toIsoString()],
    ];

    $this->app->bind(TrendResearcher::class, function () use ($fakeRawData) {
        $mock = Mockery::mock(TrendResearcher::class);
        $mock->shouldReceive('research')
            ->once()
            ->andReturn($fakeRawData);

        return $mock;
    });

    $responseJson = json_encode([
        'market_overview' => 'Restaurants are adopting more technology.',
        'trending_topics' => ['POS systems', 'Delivery apps'],
        'community_sentiment' => 'Cautiously optimistic about tech adoption.',
        'opportunities' => ['Offer digital menu solutions', 'POS consulting'],
        'talking_points' => ['Did you know 70% of restaurants are upgrading POS?', 'We helped 20 local restaurants cut costs.', 'Your competition is already online.'],
        'prediction_markets' => null,
        'relevance_score' => 78,
    ]);

    fakeAiResponse($responseJson);

    RunTrendAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadTrendAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe(LeadTrendAnalysis::STATUS_COMPLETED)
        ->and($analysis->result['market_overview'])->toBe('Restaurants are adopting more technology.')
        ->and($analysis->result['relevance_score'])->toBe(78)
        ->and($analysis->raw_data['reddit'])->toHaveCount(1);

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'trend_analysis_completed')->exists())->toBeTrue();
});

it('uses topic from existing analysis record when already set', function (): void {
    $admin = actingAsAdmin();
    AiSetting::factory()->create();

    $lead = Lead::factory()->create(['title' => 'Acme Corp', 'category' => 'Tech']);

    LeadTrendAnalysis::create([
        'lead_id' => $lead->id,
        'topic' => 'custom topic override',
        'status' => LeadTrendAnalysis::STATUS_PENDING,
        'started_at' => now(),
    ]);

    $capturedTopic = null;

    $this->app->bind(TrendResearcher::class, function () use (&$capturedTopic) {
        $mock = Mockery::mock(TrendResearcher::class);
        $mock->shouldReceive('research')
            ->once()
            ->andReturnUsing(function (string $topic) use (&$capturedTopic) {
                $capturedTopic = $topic;

                return ['reddit' => [], 'hackernews' => [], 'polymarket' => [], 'meta' => ['topic' => $topic, 'days' => 30, 'fetched_at' => now()->toIsoString()]];
            });

        return $mock;
    });

    fakeAiResponse(json_encode([
        'market_overview' => 'Overview.',
        'trending_topics' => ['Topic A'],
        'community_sentiment' => 'Positive.',
        'opportunities' => ['Opportunity A'],
        'talking_points' => ['Point A', 'Point B', 'Point C'],
        'prediction_markets' => null,
        'relevance_score' => 60,
    ]));

    RunTrendAnalysisJob::dispatchSync($lead, $admin->id);

    expect($capturedTopic)->toBe('custom topic override');
});

it('marks analysis as failed and notifies user on error', function (): void {
    Notification::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadTrendAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadTrendAnalysis::STATUS_PENDING,
        'started_at' => now(),
    ]);

    $job = new RunTrendAnalysisJob($lead, $admin->id);
    $job->failed(new RuntimeException('AI returned invalid JSON.'));

    $analysis = LeadTrendAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis->status)->toBe(LeadTrendAnalysis::STATUS_FAILED)
        ->and($analysis->error_message)->toBe('AI returned invalid JSON.');

    Notification::assertSentTo($admin, TrendAnalysisFailedNotification::class);
});
