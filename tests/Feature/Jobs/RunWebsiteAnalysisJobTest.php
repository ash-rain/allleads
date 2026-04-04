<?php

use App\Jobs\RunWebsiteAnalysisJob;
use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadWebsiteAnalysis;
use App\Notifications\WebsiteAnalysisFailedNotification;
use App\Services\Intelligence\WebsiteScraper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches RunWebsiteAnalysisJob', function (): void {
    Queue::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    RunWebsiteAnalysisJob::dispatch($lead, $admin->id);

    Queue::assertPushed(RunWebsiteAnalysisJob::class);
});

it('creates a completed website analysis with scraped data and AI result', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create([
        'provider' => 'openrouter',
        'model' => 'meta-llama/llama-3.1-8b-instruct:free',
        'temperature' => 0.3,
        'max_tokens' => 2000,
    ]);

    $lead = Lead::factory()->create([
        'title' => 'Acme Web Co',
        'website' => 'https://acme.example.com',
    ]);

    // Mock the scraper to return controlled data
    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldReceive('scrape')
            ->once()
            ->with('https://acme.example.com')
            ->andReturn([
                'company_name' => 'Acme Web Co',
                'tech_stack' => ['WordPress'],
                'social_links' => ['linkedin' => 'https://linkedin.com/company/acme'],
                'team_members' => [],
                'pricing_tiers' => [],
                'job_postings' => [],
                'contact_info' => ['email' => 'info@acme.example.com'],
                'company_size_signals' => null,
            ]);

        return $mock;
    });

    $responseJson = json_encode([
        'business_overview' => 'Acme is a web agency.',
        'value_proposition' => 'They build websites.',
        'target_market' => 'SMBs',
        'revenue_model' => 'Project-based',
        'competitive_position' => 'Mid-market',
        'growth_signals' => 'Hiring developers',
        'tech_maturity' => 'Medium',
        'sales_angles' => ['Offer SEO services', 'Upsell hosting'],
        'pain_points' => ['Slow site speed'],
        'overall_score' => 72,
    ]);

    fakeAiResponse($responseJson);

    RunWebsiteAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadWebsiteAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe(LeadWebsiteAnalysis::STATUS_COMPLETED)
        ->and($analysis->result['business_overview'])->toBe('Acme is a web agency.')
        ->and($analysis->result['overall_score'])->toBe(72)
        ->and($analysis->scraped_data['tech_stack'])->toContain('WordPress');

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'website_analysis_completed')->exists())->toBeTrue();
});

it('skips scraping when lead has no website', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create();

    $lead = Lead::factory()->create(['website' => null]);

    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldNotReceive('scrape');

        return $mock;
    });

    $responseJson = json_encode([
        'business_overview' => 'Unknown business.',
        'value_proposition' => 'Unknown.',
        'target_market' => 'Unknown.',
        'revenue_model' => 'Unknown.',
        'competitive_position' => 'Unknown.',
        'growth_signals' => 'None.',
        'tech_maturity' => 'Low.',
        'sales_angles' => ['Sell website'],
        'pain_points' => ['No online presence'],
        'overall_score' => 30,
    ]);

    fakeAiResponse($responseJson);

    RunWebsiteAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadWebsiteAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis->status)->toBe(LeadWebsiteAnalysis::STATUS_COMPLETED)
        ->and($analysis->scraped_data)->toBeArray()
        ->and($analysis->scraped_data)->toBeEmpty();
});

it('marks analysis as failed and notifies user on error', function (): void {
    Notification::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadWebsiteAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadWebsiteAnalysis::STATUS_PENDING,
        'started_at' => now(),
    ]);

    $job = new RunWebsiteAnalysisJob($lead, $admin->id);
    $job->failed(new RuntimeException('AI returned invalid JSON.'));

    $analysis = LeadWebsiteAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis->status)->toBe(LeadWebsiteAnalysis::STATUS_FAILED)
        ->and($analysis->error_message)->toBe('AI returned invalid JSON.');

    Notification::assertSentTo($admin, WebsiteAnalysisFailedNotification::class);
});

it('includes the configured language in the system prompt', function (): void {
    AiSetting::factory()->create(['language' => 'German']);

    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldReceive('scrape')->andReturn([]);

        return $mock;
    });

    fakeAiResponse(json_encode([
        'business_overview' => 'Ein Unternehmen.',
        'value_proposition' => 'Websites.',
        'target_market' => 'KMU',
        'revenue_model' => 'Projektbasiert',
        'competitive_position' => 'Mittelmarkt',
        'growth_signals' => 'Keine',
        'tech_maturity' => 'Mittel',
        'sales_angles' => ['SEO anbieten'],
        'pain_points' => ['Langsame Website'],
        'overall_score' => 60,
    ]));

    $lead = Lead::factory()->create(['website' => 'https://example.de']);
    $admin = actingAsAdmin();

    RunWebsiteAnalysisJob::dispatchSync($lead, $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'German');
    });
});

it('uses key_services from BusinessSetting in the overall_score description', function (): void {
    AiSetting::factory()->create();

    BusinessSetting::factory()->create([
        'key_services' => 'Mobile App Development',
    ]);

    $this->app->bind(WebsiteScraper::class, function () {
        $mock = Mockery::mock(WebsiteScraper::class);
        $mock->shouldReceive('scrape')->andReturn([]);

        return $mock;
    });

    fakeAiResponse(json_encode([
        'business_overview' => 'A business.',
        'value_proposition' => 'Value.',
        'target_market' => 'SMBs.',
        'revenue_model' => 'Project.',
        'competitive_position' => 'Mid.',
        'growth_signals' => 'None.',
        'tech_maturity' => 'Low.',
        'sales_angles' => ['Angle one'],
        'pain_points' => ['Issue one'],
        'overall_score' => 50,
    ]));

    $lead = Lead::factory()->create(['website' => 'https://example.com']);
    $admin = actingAsAdmin();

    RunWebsiteAnalysisJob::dispatchSync($lead, $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'Mobile App Development');
    });
});
