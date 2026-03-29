<?php

use App\Models\EmailThread;
use App\Models\Lead;
use App\Services\Brevo\BrevoInboundParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves thread from reply+ address', function (): void {
    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    $payload = [
        'to' => ["reply+{$thread->id}@inbound.example.com"],
        'from' => 'customer@example.com',
        'subject' => 'Re: Hello',
        'text' => 'Thanks for reaching out!',
    ];

    $parser = new BrevoInboundParser;
    $resolved = $parser->resolveThread($payload);

    expect($resolved?->id)->toBe($thread->id);
});

it('resolves thread from X-Thread-ID header', function (): void {
    $lead = Lead::factory()->create();
    $thread = EmailThread::factory()->create(['lead_id' => $lead->id]);

    $payload = [
        'to' => ['generic@example.com'],
        'headers' => ['X-Thread-ID' => (string) $thread->id],
        'text' => 'Reply body',
    ];

    $parser = new BrevoInboundParser;
    expect($parser->resolveThread($payload)?->id)->toBe($thread->id);
});

it('returns null when no thread can be found', function (): void {
    $payload = [
        'to' => ['nobody@example.com'],
        'text' => 'Unsolicited email',
    ];

    $parser = new BrevoInboundParser;
    expect($parser->resolveThread($payload))->toBeNull();
});
