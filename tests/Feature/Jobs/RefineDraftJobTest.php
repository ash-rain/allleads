<?php

use App\Jobs\RefineDraftJob;
use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Notifications\DraftFailedNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches RefineDraftJob', function (): void {
    Queue::fake();

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create(['lead_id' => $lead->id, 'thread_id' => $thread->id]);
    $admin = actingAsAdmin();

    RefineDraftJob::dispatch($draft, 'Make it shorter.', $admin->id);

    Queue::assertPushed(RefineDraftJob::class);
});

it('updates draft body with AI-refined content', function (): void {
    AiSetting::factory()->create();
    BusinessSetting::factory()->create();

    fakeAiResponse('Refined email body here.');

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
        'status' => 'draft',
    ]);
    $admin = actingAsAdmin();

    RefineDraftJob::dispatchSync($draft, 'Shorten it.', $admin->id);

    expect($draft->fresh()->body)->toBe('Refined email body here.')
        ->and($draft->fresh()->status)->toBe('draft');
});

it('saves a version snapshot before overwriting the draft body', function (): void {
    AiSetting::factory()->create();
    BusinessSetting::factory()->create();

    fakeAiResponse('Refined body.');

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
    ]);
    $admin = actingAsAdmin();

    RefineDraftJob::dispatchSync($draft, 'Make it shorter.', $admin->id);

    expect($draft->versions()->count())->toBeGreaterThanOrEqual(1);
});

it('includes business context in the system prompt', function (): void {
    AiSetting::factory()->create();

    BusinessSetting::factory()->create([
        'business_name' => 'My Agency',
        'business_description' => 'We build amazing apps.',
    ]);

    fakeAiResponse('Refined body.');

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
    ]);
    $admin = actingAsAdmin();

    RefineDraftJob::dispatchSync($draft, 'Make it shorter.', $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'My Agency');
    });
});

it('includes language from AiSetting in the system prompt', function (): void {
    AiSetting::factory()->create(['language' => 'Spanish']);
    BusinessSetting::factory()->create();

    fakeAiResponse('Refined body.');

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
    ]);
    $admin = actingAsAdmin();

    RefineDraftJob::dispatchSync($draft, 'Make it shorter.', $admin->id);

    Http::assertSent(function ($request): bool {
        $decoded = json_decode($request->body(), true);
        $systemContent = collect($decoded['messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        return str_contains($systemContent, 'Spanish');
    });
});

it('marks draft as failed and notifies user when job fails', function (): void {
    Notification::fake();

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
        'status' => 'draft',
    ]);
    $admin = actingAsAdmin();

    $job = new RefineDraftJob($draft, 'Make it shorter.', $admin->id);
    $job->failed(new RuntimeException('AI timeout.'));

    expect($draft->fresh()->status)->toBe('failed');
    Notification::assertSentTo($admin, DraftFailedNotification::class);
});
