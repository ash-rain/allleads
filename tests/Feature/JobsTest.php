<?php

use App\Jobs\GenerateColdEmailJob;
use App\Jobs\SendEmailJob;
use App\Livewire\ConversationView;
use App\Models\AiSetting;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('dispatches GenerateColdEmailJob from ConversationView', function (): void {
    Queue::fake();
    actingAsAdmin();

    $lead = Lead::factory()->create(['email' => 'lead@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    Livewire::test(ConversationView::class, ['leadId' => $lead->id])
        ->call('generateAiDraft');

    Queue::assertPushed(GenerateColdEmailJob::class);
});

it('GenerateColdEmailJob creates a draft', function (): void {
    fakeAiResponse('Hello, I noticed you do not have a website …');

    AiSetting::factory()->create([
        'provider' => 'openrouter',
        'model' => 'meta-llama/llama-3.1-8b-instruct:free',
        'temperature' => 0.7,
        'max_tokens' => 800,
    ]);

    $lead = Lead::factory()->create(['email' => 'a@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    $admin = actingAsAdmin();

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});

it('SendEmailJob marks draft as sent', function (): void {
    fakeBrevoResponse('<msg-123@brevo>');

    $lead = Lead::factory()->create(['email' => 'b@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
        'subject' => 'Test',
        'body' => 'Body',
        'status' => 'draft',
    ]);

    $admin = actingAsAdmin();

    SendEmailJob::dispatchSync($draft, $admin->id);

    expect($draft->fresh()->status)->toBe('sent');

    expect(LeadActivity::where('lead_id', $lead->id)->where('event', 'email_sent')->exists())->toBeTrue();
});

// ─── ConversationView: deleteDraft ───────────────────────────────────────────

it('admin can delete a draft via ConversationView', function (): void {
    actingAsAdmin();

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
    ]);

    Livewire::test(ConversationView::class, ['leadId' => $lead->id])
        ->call('deleteDraft', $draft->id)
        ->assertHasNoErrors();

    expect(EmailDraft::withTrashed()->find($draft->id)->deleted_at)->not->toBeNull();
    expect(EmailDraft::find($draft->id))->toBeNull();
});

it('deleteDraft closes the draft editor when the open draft is deleted', function (): void {
    actingAsAdmin();

    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft = EmailDraft::factory()->create([
        'lead_id' => $lead->id,
        'thread_id' => $thread->id,
    ]);

    Livewire::test(ConversationView::class, ['leadId' => $lead->id])
        ->call('openDraftEditor', $draft->id)
        ->assertSet('showDraftEditor', true)
        ->assertSet('selectedDraftId', $draft->id)
        ->call('deleteDraft', $draft->id)
        ->assertSet('showDraftEditor', false)
        ->assertSet('selectedDraftId', null);
});
