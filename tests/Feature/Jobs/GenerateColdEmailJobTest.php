<?php

use App\Ai\Agents\ColdEmailAgent;
use App\Jobs\GenerateColdEmailJob;
use App\Models\Business;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;

it('includes prospect analysis in prompt when completed', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['title' => 'Test Corp', 'email' => 'test@corp.com', 'business_id' => $business->id]);
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

    ColdEmailAgent::fake([[
        'subject' => 'Test subject',
        'body' => 'Cold email body here.',
    ]]);

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});

it('includes website analysis in prompt when completed', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['title' => 'Test Corp', 'email' => 'test@corp.com', 'business_id' => $business->id]);
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

    ColdEmailAgent::fake([[
        'subject' => 'Test subject',
        'body' => 'Cold email body here.',
    ]]);

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});

it('generates email without analysis when none exists', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['title' => 'Plain Lead', 'email' => 'plain@lead.com', 'business_id' => $business->id]);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    ColdEmailAgent::fake([[
        'subject' => 'Test subject',
        'body' => 'Cold email body here.',
    ]]);

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'draft_generated')->exists())->toBeTrue();
});

it('includes business context — verifies draft is created for a named business', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create([
        'name' => 'Pixel Studio',
        'description' => 'We design beautiful websites.',
    ]);

    $lead = Lead::factory()->create(['title' => 'Test Corp', 'email' => 'test@corp.com', 'business_id' => $business->id]);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    ColdEmailAgent::fake([[
        'subject' => 'Email for Pixel Studio lead',
        'body' => 'Email body here.',
    ]]);

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    $draft = EmailDraft::where('lead_id', $lead->id)->firstOrFail();
    expect($draft->subject)->toBe('Email for Pixel Studio lead');
});

it('stores subject and body from structured agent response', function (): void {
    $admin = actingAsAdmin();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['title' => 'Тест ЕООД', 'email' => 'test@bg.com', 'business_id' => $business->id]);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    ColdEmailAgent::fake([[
        'subject' => 'Бърз въпрос за онлайн присъствието ви',
        'body' => 'Здравейте, видях, че нямате уебсайт.',
    ]]);

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    $draft = EmailDraft::where('lead_id', $lead->id)->firstOrFail();

    expect($draft->subject)->toBe('Бърз въпрос за онлайн присъствието ви')
        ->and($draft->body)->toBe('Здравейте, видях, че нямате уебсайт.');
});
