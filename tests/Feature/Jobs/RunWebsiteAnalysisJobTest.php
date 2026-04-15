<?php

use App\Ai\Agents\WebsiteAnalysisAgent;
use App\Jobs\RunWebsiteAnalysisJob;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadWebsiteAnalysis;
use App\Notifications\WebsiteAnalysisFailedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches RunWebsiteAnalysisJob', function (): void {
    Queue::fake();

    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['business_id' => $business->id]);

    RunWebsiteAnalysisJob::dispatch($lead, $admin->id);

    Queue::assertPushed(RunWebsiteAnalysisJob::class);
});

it('creates a completed website analysis', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create([
        'title' => 'Acme Web Co',
        'website' => 'https://acme.example.com',
        'business_id' => $business->id,
    ]);

    WebsiteAnalysisAgent::fake([[
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
    ]]);

    RunWebsiteAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadWebsiteAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe(LeadWebsiteAnalysis::STATUS_COMPLETED)
        ->and($analysis->result['business_overview'])->toBe('Acme is a web agency.')
        ->and($analysis->result['overall_score'])->toBe(72);

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'website_analysis_completed')->exists())->toBeTrue();
});

it('creates a completed website analysis when lead has no website', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['website' => null, 'business_id' => $business->id]);

    WebsiteAnalysisAgent::fake([[
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
    ]]);

    RunWebsiteAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadWebsiteAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis->status)->toBe(LeadWebsiteAnalysis::STATUS_COMPLETED);
});

it('marks analysis as failed and notifies user on error', function (): void {
    Notification::fake();

    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['business_id' => $business->id]);

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
