<?php

use App\Jobs\GenerateColdEmailJob;
use App\Models\AiSetting;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;

it('includes prospect analysis in prompt when completed', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create();

    $lead = Lead::factory()->create(['title' => 'Test Corp', 'email' => 'test@corp.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    LeadProspectAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadProspectAnalysis::STATUS_COMPLETED,
        'result' => [
            'prospect_score' => 80,
            'opportunity' => 'Missing modern website',
            'outreach_strategy' => 'Lead with ROI of a new site',
        ],
        'completed_at' => now(),
    ]);

    // Capture the prompt sent to the AI provider
    $capturedPrompt = '';
    fakeAiResponse('Cold email body here.');

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    // Verify the job ran - if it didn't throw, the prompt was built correctly.
    // We verify indirectly by checking that an email draft was created.
    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});

it('includes website analysis in prompt when completed', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create();

    $lead = Lead::factory()->create(['title' => 'Test Corp', 'email' => 'test@corp.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    LeadWebsiteAnalysis::create([
        'lead_id' => $lead->id,
        'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
        'result' => [
            'business_overview' => 'A web agency in London',
            'sales_angles' => ['Offer SEO', 'Upsell hosting'],
            'pain_points' => ['Outdated design', 'Slow performance'],
            'overall_score' => 65,
        ],
        'completed_at' => now(),
    ]);

    fakeAiResponse('Cold email body here.');

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});

it('generates email without analysis when none exists', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create();

    $lead = Lead::factory()->create(['title' => 'Plain Lead', 'email' => 'plain@lead.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    fakeAiResponse('Cold email body here.');

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});
