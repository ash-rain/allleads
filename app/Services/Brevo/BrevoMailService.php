<?php

namespace App\Services\Brevo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends transactional emails through the Brevo v3 API.
 * Adds X-Lead-ID and X-Thread-ID custom headers so inbound webhooks
 * can correlate replies to the correct thread.
 */
class BrevoMailService
{
    private const API_BASE = 'https://api.brevo.com/v3';

    private readonly string $apiKey;

    private readonly string $fromEmail;

    private readonly string $fromName;

    public function __construct(
        string $apiKey = '',
        string $fromEmail = '',
        string $fromName = '',
    ) {
        $this->apiKey = $apiKey ?: (string) config('services.brevo.api_key', '');
        $this->fromEmail = $fromEmail ?: (string) config('mail.from.address', 'hello@example.com');
        $this->fromName = $fromName ?: (string) config('mail.from.name', 'AllLeads');
    }

    /**
     * Send a transactional email and return the Brevo message ID.
     *
     * @throws \RuntimeException On API error.
     */
    public function send(
        string $to,
        string $toName,
        string $subject,
        string $body,
        int $leadId,
        int $threadId,
    ): string {
        $replyTo = $this->buildReplyToAddress($threadId);

        $payload = [
            'sender' => ['name' => $this->fromName, 'email' => $this->fromEmail],
            'to' => [['email' => $to, 'name' => $toName]],
            'replyTo' => ['email' => $replyTo],
            'subject' => $subject,
            'textContent' => $body,
            'headers' => [
                'X-Lead-ID' => (string) $leadId,
                'X-Thread-ID' => (string) $threadId,
            ],
        ];

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::API_BASE.'/smtp/email', $payload);

        if ($response->failed()) {
            $error = $response->json('message') ?? $response->body();
            Log::error('Brevo send failed', ['error' => $error, 'lead_id' => $leadId]);
            throw new \RuntimeException("Brevo API error: {$error}");
        }

        return $response->json('messageId') ?? '';
    }

    /**
     * Build a reply-to address that encodes the thread ID so inbound parsing
     * can identify the thread without relying on In-Reply-To headers.
     * Format: reply+{threadId}@{inbound_domain}
     */
    private function buildReplyToAddress(int $threadId): string
    {
        $domain = config('services.brevo.inbound_domain', 'inbound.example.com');

        return "reply+{$threadId}@{$domain}";
    }
}
