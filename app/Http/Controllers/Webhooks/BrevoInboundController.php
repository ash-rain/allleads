<?php

namespace App\Http\Controllers\Webhooks;

use App\Events\LeadRepliedEvent;
use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Services\Brevo\BrevoInboundParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles Brevo inbound email webhook.
 * Configure in Brevo → Inbound Parsing → Webhook URL → /webhooks/brevo/inbound
 */
class BrevoInboundController extends Controller
{
    public function __invoke(Request $request, BrevoInboundParser $parser): Response
    {
        $payload = $request->json()->all();

        Log::debug('Brevo inbound webhook received', ['keys' => array_keys($payload)]);

        $thread = $parser->resolveThread($payload);

        if (! $thread) {
            // We could not match a thread — log and accept (returning 200 stops retries).
            Log::warning('Brevo inbound: could not resolve thread', [
                'from' => $parser->senderEmail($payload),
            ]);

            return response()->json(['status' => 'ignored'], Response::HTTP_OK);
        }

        $lead = $thread->lead;

        $message = EmailMessage::create([
            'thread_id' => $thread->id,
            'role' => 'lead_reply',
            'subject' => $parser->subject($payload),
            'body' => $parser->textBody($payload),
            'message_id' => $payload['MessageID'] ?? $payload['message_id'] ?? null,
            'sender' => $parser->senderEmail($payload),
            'source' => 'brevo_inbound',
            'sent_at' => now(),
        ]);

        LeadRepliedEvent::dispatch($lead, $thread, $message);

        return response()->json(['status' => 'ok'], Response::HTTP_OK);
    }
}
