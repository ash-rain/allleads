<?php

use App\Jobs\RunProspectAnalysisJob;
use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadProspectAnalysis;
use App\Notifications\ProspectAnalysisFailedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches RunProspectAnalysisJob', function (): void {
    Queue::fake();

    $admin = actingAsAdmin();
    $lead = Lead::factory()->create();

    RunProspectAnalysisJob::dispatch($lead, $admin->id);

    Queue::assertPushed(RunProspectAnalysisJob::class);
});

it('RunProspectAnalysisJob creates a completed analysis', function (): void {
    $responseJson = json_encode([
        'prospect_score' => 75,
        'company_fit' => 'Great fit for web services.',
        'contact_intel' => 'Owner-operated local business.',
        'opportunity' => 'No website detected.',
        'competitive_intel' => 'Likely uses basic Google listing.',
        'outreach_strategy' => 'Lead with website ROI pitch.',
    ]);

    fakeAiResponse($responseJson);

    AiSetting::factory()->create([
        'provider' => 'openrouter',
        'model' => 'meta-llama/llama-3.1-8b-instruct:free',
        'temperature' => 0.3,
        'max_tokens' => 2000,
    ]);

    $lead = Lead::factory()->create(['title' => 'Test Business']);
    $admin = actingAsAdmin();

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

    $lead = Lead::factory()->create();
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

it('includes the configured language in the system prompt', function (): void {
    AiSetting::factory()->create(['language' => 'French']);

    fakeAiResponse(json_encode([
        'prospect_score' => 50,
        'company_fit' => 'Bon prospect.',
        'contact_intel' => 'Directeur général.',
        'opportunity' => 'Pas de site web.',
        'competitive_intel' => 'Google listing basique.',
        'outreach_strategy' => 'Proposer un site ROI.',
    ]));

    $lead = Lead::factory()->create();
    $admin = actingAsAdmin();

    RunProspectAnalysisJob::dispatchSync($lead, $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'French');
    });
});

it('includes business context in the system prompt', function (): void {
    AiSetting::factory()->create();

    BusinessSetting::factory()->create([
        'business_name' => 'Bright Digital',
        'business_description' => 'We build digital products.',
    ]);

    fakeAiResponse(json_encode([
        'prospect_score' => 60,
        'company_fit' => 'Good fit.',
        'contact_intel' => 'Decision maker.',
        'opportunity' => 'Needs digital presence.',
        'competitive_intel' => 'Uses basic website.',
        'outreach_strategy' => 'Lead with ROI.',
    ]));

    $lead = Lead::factory()->create();
    $admin = actingAsAdmin();

    RunProspectAnalysisJob::dispatchSync($lead, $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'Bright Digital');
    });
});
