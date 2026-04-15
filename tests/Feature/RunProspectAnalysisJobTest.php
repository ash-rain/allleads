<?php

use App\Ai\Agents\ProspectScoringAgent;
use App\Jobs\RunProspectAnalysisJob;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadProspectAnalysis;
use App\Notifications\ProspectAnalysisFailedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches RunProspectAnalysisJob', function (): void {
    Queue::fake();

    $admin = actingAsAdmin();
    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['business_id' => $business->id]);

    RunProspectAnalysisJob::dispatch($lead, $admin->id);

    Queue::assertPushed(RunProspectAnalysisJob::class);
});

it('RunProspectAnalysisJob creates a completed analysis', function (): void {
    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['title' => 'Test Business', 'business_id' => $business->id]);
    $admin = actingAsAdmin();

    ProspectScoringAgent::fake([[
        'prospect_score' => 75,
        'company_fit' => 'Great fit for web services.',
        'contact_intel' => 'Owner-operated local business.',
        'opportunity' => 'No website detected.',
        'competitive_intel' => 'Likely uses basic Google listing.',
        'outreach_strategy' => 'Lead with website ROI pitch.',
    ]]);

    RunProspectAnalysisJob::dispatchSync($lead, $admin->id);

    $analysis = LeadProspectAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe(LeadProspectAnalysis::STATUS_COMPLETED)
        ->and($analysis->result['prospect_score'])->toBe(75)
        ->and($analysis->result['company_fit'])->toBe('Great fit for web services.');

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'prospect_analysis_completed')->exists())->toBeTrue();
});

it('RunProspectAnalysisJob marks analysis as failed and notifies on error', function (): void {
    Notification::fake();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['business_id' => $business->id]);
    $admin = actingAsAdmin();

    // Create a pending analysis first (as handle() would do)
    LeadProspectAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadProspectAnalysis::STATUS_PENDING,
        'started_at' => now(),
    ]);

    $job = new RunProspectAnalysisJob($lead, $admin->id);
    $job->failed(new RuntimeException('AI returned invalid JSON.'));

    $analysis = LeadProspectAnalysis::where('lead_id', $lead->id)->first();

    expect($analysis->status)->toBe(LeadProspectAnalysis::STATUS_FAILED)
        ->and($analysis->error_message)->toBe('AI returned invalid JSON.');

    Notification::assertSentTo($admin, ProspectAnalysisFailedNotification::class);
});
