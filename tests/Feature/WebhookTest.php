<?php

use App\Events\LeadRepliedEvent;
use App\Http\Controllers\Webhooks\BrevoInboundController;
use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Lead;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Disable signature check in tests.
    config(['services.brevo.webhook_secret' => 'skip']);
});

it('accepts an inbound webhook and dispatches LeadRepliedEvent', function (): void {
    Event::fake([LeadRepliedEvent::class]);

    $lead   = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    $this->postJson('/api/webhooks/brevo/inbound', [
        'to'      => ["reply+{$thread->id}@inbound.example.com"],
        'from'    => 'customer@example.com',
        'subject' => 'Re: Quick question',
        'text'    => 'Hello, I am interested.',
    ])->assertOk();

    Event::assertDispatched(LeadRepliedEvent::class);

    expect(EmailMessage::where('thread_id', $thread->id)->where('role', 'lead_reply')->exists())
        ->toBeTrue();
});

it('returns ignored when thread cannot be resolved', function (): void {
    $this->postJson('/api/webhooks/brevo/inbound', [
        'to'   => ['unknown@example.com'],
        'text' => 'Spam',
    ])->assertOk()
      ->assertJson(['status' => 'ignored']);
});

it('rejects webhook with wrong signature', function (): void {
    config(['services.brevo.webhook_secret' => 'real-secret']);

    $this->postJson('/api/webhooks/brevo/inbound', ['to' => []])
        ->assertUnauthorized();
});
