<?php

use App\Ai\Agents\DraftRefinementAgent;
use App\Jobs\RefineDraftJob;
use App\Models\Business;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Notifications\DraftFailedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('dispatches RefineDraftJob', function (): void {
    Queue::fake();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['business_id' => $business->id]);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create(['lead_id' => $lead->id, 'thread_id' => $thread->id]);
    $admin = actingAsAdmin();

    RefineDraftJob::dispatch($draft, 'Make it shorter.', $admin->id);

    Queue::assertPushed(RefineDraftJob::class);
});

it('updates draft body with AI-refined content', function (): void {
    $business = Business::factory()->create();

    $lead = Lead::factory()->create(['business_id' => $business->id]);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
        'status' => 'draft',
    ]);
    $admin = actingAsAdmin();

    DraftRefinementAgent::fake([['body' => 'Refined email body here.']]);

    RefineDraftJob::dispatchSync($draft, 'Shorten it.', $admin->id);

    expect($draft->fresh()->body)->toBe('Refined email body here.')
        ->and($draft->fresh()->status)->toBe('draft');

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'draft_refined')->exists())->toBeTrue();
});

it('saves a version snapshot before overwriting the draft body', function (): void {
    $business = Business::factory()->create();

    $lead = Lead::factory()->create(['business_id' => $business->id]);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'body' => 'Original body.',
    ]);
    $admin = actingAsAdmin();

    DraftRefinementAgent::fake([['body' => 'Refined body.']]);

    RefineDraftJob::dispatchSync($draft, 'Make it shorter.', $admin->id);

    expect($draft->versions()->count())->toBeGreaterThanOrEqual(1);
});

it('marks draft as failed and notifies user when job fails', function (): void {
    Notification::fake();

    $business = Business::factory()->create();
    $lead = Lead::factory()->create(['business_id' => $business->id]);
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
