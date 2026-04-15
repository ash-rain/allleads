<?php

use App\Ai\Agents\GeoAnalysisAgent;
use App\Jobs\RunGeoAnalysisJob;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadGeoAnalysis;
use App\Notifications\GeoAnalysisFailedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches RunGeoAnalysisJob', function (): void {
    Queue::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    RunGeoAnalysisJob::dispatch($lead, $admin->id);

    Queue::assertPushed(RunGeoAnalysisJob::class);
});

it('creates a completed geo analysis with AI result', function (): void {
    $admin = actingAsAdmin();

    $lead = Lead::factory()->create([
        'title' => 'Sunset Restaurant',
        'website' => 'https://sunset-restaurant.example.com',
    ]);

    GeoAnalysisAgent::fake([[
        'geo_score' => 68,
        'ai_visibility_summary' => 'Site has moderate AI visibility with room to improve.',
        'citability_assessment' => 'Content is reasonably citable but lacks structured data.',
        'crawler_access_summary' => 'Most AI crawlers have access.',
        'brand_authority_assessment' => 'No Wikipedia presence found.',
        'schema_assessment' => 'Missing Organization schema markup.',
        'technical_assessment' => 'Site loads quickly but lacks llms.txt.',
        'sales_angles' => ['Offer GEO audit service', 'Schema markup implementation'],
        'quick_wins' => ['Add Organization schema', 'Create llms.txt file'],
        'platform_recommendations' => ['Add to Google Business', 'Create Wikidata entry'],
    ]]);

    RunGeoAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadGeoAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe(LeadGeoAnalysis::STATUS_COMPLETED)
        ->and($analysis->result['geo_score'])->toBe(68)
        ->and($analysis->result['ai_visibility_summary'])->toBe('Site has moderate AI visibility with room to improve.')
        ->and($analysis->result['sales_angles'])->toHaveCount(2);

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'geo_analysis_completed')->exists())->toBeTrue();
});

it('creates a completed geo analysis for leads with no website', function (): void {
    $admin = actingAsAdmin();

    $lead = Lead::factory()->create([
        'title' => 'No Website Corp',
        'website' => null,
    ]);

    GeoAnalysisAgent::fake([[
        'geo_score' => 10,
        'ai_visibility_summary' => 'No web presence detected.',
        'citability_assessment' => 'No website to assess.',
        'crawler_access_summary' => 'No website.',
        'brand_authority_assessment' => 'No Wikipedia presence.',
        'schema_assessment' => 'Not applicable.',
        'technical_assessment' => 'Not applicable.',
        'sales_angles' => ['Help establish web presence'],
        'quick_wins' => ['Register domain'],
        'platform_recommendations' => ['Create Google Business profile'],
    ]]);

    RunGeoAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadGeoAnalysis::where('lead_id', $lead->id)->first();
    expect($analysis->status)->toBe(LeadGeoAnalysis::STATUS_COMPLETED)
        ->and($analysis->result['geo_score'])->toBe(10);
});

it('marks analysis as failed and notifies user on error', function (): void {
    Notification::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadGeoAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadGeoAnalysis::STATUS_PENDING,
        'started_at' => now(),
    ]);

    $job = new RunGeoAnalysisJob($lead, $admin->id);
    $job->failed(new RuntimeException('AI returned invalid JSON.'));

    $analysis = LeadGeoAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis->status)->toBe(LeadGeoAnalysis::STATUS_FAILED)
        ->and($analysis->error_message)->toBe('AI returned invalid JSON.');

    Notification::assertSentTo($admin, GeoAnalysisFailedNotification::class);
});

it('records a lead activity on analysis failure', function (): void {
    Notification::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    LeadGeoAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadGeoAnalysis::STATUS_PENDING,
        'started_at' => now(),
    ]);

    $job = new RunGeoAnalysisJob($lead, $admin->id);
    $job->failed(new RuntimeException('Connection timeout.'));

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'geo_analysis_failed')->exists())->toBeTrue();
});
