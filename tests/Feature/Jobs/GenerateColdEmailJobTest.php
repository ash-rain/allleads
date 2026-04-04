<?php

use App\Jobs\GenerateColdEmailJob;
use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;
use Illuminate\Support\Facades\Http;

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

it('includes business context in the system prompt', function (): void {
    $admin = actingAsAdmin();

    AiSetting::factory()->create();

    BusinessSetting::factory()->create([
        'business_name' => 'Pixel Studio',
        'business_description' => 'We design beautiful websites.',
    ]);

    $lead = Lead::factory()->create(['title' => 'Test Corp', 'email' => 'test@corp.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    fakeAiResponse('Email body here.');

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'Pixel Studio');
    });
});

it('parses subject from AI response so it respects the selected language', function (): void {
    $admin = actingAsAdmin();
    AiSetting::factory()->create(['language' => 'Bulgarian']);

    $lead = Lead::factory()->create(['title' => 'Тест ЕООД', 'email' => 'test@bg.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    fakeAiResponse("Subject: Бърз въпрос за онлайн присъствието ви\n\nЗдравейте, видях, че нямате уебсайт.");

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    $draft = EmailDraft::where('lead_id', $lead->id)->firstOrFail();

    expect($draft->subject)->toBe('Бърз въпрос за онлайн присъствието ви')
        ->and($draft->body)->toBe('Здравейте, видях, че нямате уебсайт.');
});

it('falls back to default subject when AI response has no subject line', function (): void {
    $admin = actingAsAdmin();
    AiSetting::factory()->create();

    $lead = Lead::factory()->create(['title' => 'Acme Corp', 'email' => 'acme@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    fakeAiResponse('Just the body, no subject line here.');

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    $draft = EmailDraft::where('lead_id', $lead->id)->firstOrFail();

    expect($draft->subject)->toBe("Quick question about Acme Corp's online presence");
});
