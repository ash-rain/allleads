<?php

use App\Jobs\GenerateColdEmailJob;
use App\Jobs\SendEmailJob;
use App\Models\AiSetting;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('dispatches GenerateColdEmailJob from ConversationView', function (): void {
    Queue::fake();
    actingAsAdmin();

    $lead   = Lead::factory()->create(['email' => 'lead@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    \Livewire\Livewire::test(\App\Livewire\ConversationView::class, ['leadId' => $lead->id])
        ->call('generateAiDraft');

    Queue::assertPushed(GenerateColdEmailJob::class);
});

it('GenerateColdEmailJob creates a draft', function (): void {
    fakeAiResponse('Hello, I noticed you do not have a website …');

    AiSetting::factory()->create([
        'provider' => 'openrouter',
        'model'    => 'meta-llama/llama-3.1-8b-instruct:free',
        'temperature' => 0.7,
        'max_tokens'  => 800,
    ]);

    $lead   = Lead::factory()->create(['email' => 'a@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    $admin = actingAsAdmin();

    GenerateColdEmailJob::dispatchSync($lead, $thread, null, $admin->id);

    expect(EmailDraft::where('lead_id', $lead->id)->count())->toBe(1);
});

it('SendEmailJob marks draft as sent', function (): void {
    fakeBrevoResponse('<msg-123@brevo>');

    $lead   = Lead::factory()->create(['email' => 'b@example.com']);
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);
    $draft  = EmailDraft::factory()->create([
        'lead_id'   => $lead->id,
        'thread_id' => $thread->id,
        'subject'   => 'Test',
        'body'      => 'Body',
        'status'    => 'pending',
    ]);

    $admin = actingAsAdmin();

    SendEmailJob::dispatchSync($draft, $admin->id);

    expect($draft->fresh()->status)->toBe('sent');
});
