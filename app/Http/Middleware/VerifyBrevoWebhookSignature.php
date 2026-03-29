<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies Brevo webhook HMAC-SHA256 signature.
 * Brevo sends the signature in the X-Brevo-Signature or X-Mailin-Alert headers.
 *
 * Set BREVO_WEBHOOK_SECRET in .env.
 * Skip verification in tests by setting BREVO_WEBHOOK_SECRET=skip.
 */
class VerifyBrevoWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.brevo.webhook_secret', '');

        if ($secret === '' || $secret === 'skip') {
            return $next($request);
        }

        $signature = $request->header('X-Brevo-Signature')
            ?? $request->header('X-Mailin-Alert')
            ?? '';

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, strtolower($signature))) {
            Log::warning('Invalid Brevo webhook signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
