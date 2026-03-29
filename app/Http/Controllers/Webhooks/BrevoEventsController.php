<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\EmailDraft;
use App\Models\EmailMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles Brevo transactional email delivery event webhooks.
 * Configure in Brevo → Transactional → Webhooks → all delivery events.
 * Webhook URL: /webhooks/brevo/events
 */
class BrevoEventsController extends Controller
{
    /**
     * Brevo sends an array of event objects per request.
     */
    public function __invoke(Request $request): Response
    {
        $events = $request->json()->all();

        // Brevo can batch multiple events per payload.
        if (isset($events['event'])) {
            $events = [$events];
        }

        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return response()->json(['status' => 'ok'], Response::HTTP_OK);
    }

    private function handleEvent(array $event): void
    {
        $eventType = $event['event'] ?? '';
        $messageId = $event['MessageID'] ?? $event['message-id'] ?? null;

        if (! $messageId) {
            return;
        }

        Log::debug('Brevo delivery event', ['event' => $eventType, 'message_id' => $messageId]);

        switch ($eventType) {
            case 'delivered':
                EmailMessage::where('message_id', $messageId)
                    ->update(['sent_at' => now()]);
                break;

            case 'soft_bounce':
            case 'hard_bounce':
            case 'invalid_email':
            case 'blocked':
                $this->markDeliveryFailed($messageId, $eventType, $event['reason'] ?? '');
                break;

            default:
                // 'opened', 'clicked', 'unsubscribed', etc. — informational only.
                break;
        }
    }

    private function markDeliveryFailed(string $messageId, string $eventType, string $reason): void
    {
        $message = EmailMessage::where('message_id', $messageId)->first();

        if (! $message) {
            return;
        }

        // Find the draft linked to this thread and mark it failed.
        EmailDraft::where('thread_id', $message->thread_id)
            ->where('status', 'sent')
            ->update([
                'status' => 'failed',
                'error' => "Delivery failed ({$eventType}): {$reason}",
            ]);

        Log::warning('Email delivery failed', [
            'message_id' => $messageId,
            'event' => $eventType,
            'reason' => $reason,
        ]);
    }
}
