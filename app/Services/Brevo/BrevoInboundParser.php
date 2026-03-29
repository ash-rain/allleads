<?php

namespace App\Services\Brevo;

use App\Models\EmailThread;

/**
 * Parses Brevo inbound email webhook payloads and resolves
 * the associated EmailThread.
 */
class BrevoInboundParser
{
    /**
     * Resolve a thread from the inbound Brevo webhook payload.
     * Tries (in order):
     * 1. The `reply+{id}@…` address in the To/CC list.
     * 2. The X-Thread-ID header from the original message.
     * 3. The In-Reply-To / References header mapped to a stored message_id.
     *
     * Returns null when no thread can be identified.
     */
    public function resolveThread(array $payload): ?EmailThread
    {
        // 1. Encoded reply-to address —  reply+{threadId}@{domain}
        if ($threadId = $this->extractFromAddress($payload)) {
            return EmailThread::find($threadId);
        }

        // 2. X-Thread-ID header
        if ($threadId = $this->extractFromHeaders($payload, 'X-Thread-ID')) {
            return EmailThread::find((int) $threadId);
        }

        // 3. In-Reply-To / References
        if ($messageId = $this->extractInReplyTo($payload)) {
            return \App\Models\EmailMessage::where('message_id', $messageId)
                ->first()
                ?->thread;
        }

        return null;
    }

    /**
     * Extract the sender email address from the payload.
     */
    public function senderEmail(array $payload): string
    {
        return $payload['From'] ?? $payload['from'] ?? '';
    }

    /**
     * Extract the plain text body from the payload.
     */
    public function textBody(array $payload): string
    {
        return $payload['text'] ?? $payload['TextBody'] ?? $payload['html'] ?? '';
    }

    /**
     * Extract subject from the payload.
     */
    public function subject(array $payload): string
    {
        return $payload['subject'] ?? $payload['Subject'] ?? '(no subject)';
    }

    // ─── Private Helpers ────────────────────────────────────────────────────

    private function extractFromAddress(array $payload): ?int
    {
        $addresses = array_merge(
            $this->addressList($payload['to'] ?? []),
            $this->addressList($payload['cc'] ?? []),
        );

        foreach ($addresses as $addr) {
            if (preg_match('/reply\+(\d+)@/i', $addr, $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }

    private function extractFromHeaders(array $payload, string $header): ?string
    {
        $headers = $payload['headers'] ?? $payload['Headers'] ?? [];

        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $header) === 0) {
                return (string) $value;
            }
        }

        return null;
    }

    private function extractInReplyTo(array $payload): ?string
    {
        $value = $this->extractFromHeaders($payload, 'In-Reply-To')
            ?? $this->extractFromHeaders($payload, 'References');

        if (! $value) {
            return null;
        }

        // Take the first message-id token.
        return trim(explode(' ', $value)[0], '<>');
    }

    /** Flatten Brevo address formats to plain email strings. */
    private function addressList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (is_array($value)) {
            return array_map(static fn($item) => is_array($item) ? ($item['email'] ?? '') : (string) $item, $value);
        }

        return [];
    }
}
