<?php

namespace App\Jobs;

use App\Models\EmailDraft;
use App\Models\EmailMessage;
use App\Notifications\DraftFailedNotification;
use App\Services\Brevo\BrevoMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;

    public function __construct(
        public readonly EmailDraft $draft,
        public readonly int        $userId,
    ) {}

    public function handle(BrevoMailService $brevo): void
    {
        if ($this->draft->status === 'sent') {
            return; // idempotent guard
        }

        $thread = $this->draft->thread()->with('lead')->firstOrFail();
        $lead   = $thread->lead;

        if (! $lead->email) {
            $this->fail(new \RuntimeException("Lead #{$lead->id} has no email address."));
            return;
        }

        $messageId = $brevo->send(
            to: $lead->email,
            toName: $lead->title,
            subject: $this->draft->subject,
            body: $this->draft->body,
            leadId: $lead->id,
            threadId: $thread->id,
        );

        // Record the sent message in the thread.
        EmailMessage::create([
            'thread_id'  => $thread->id,
            'role'       => 'outbound',
            'subject'    => $this->draft->subject,
            'body'       => $this->draft->body,
            'message_id' => $messageId,
            'sender'     => config('mail.from.address'),
            'source'     => 'brevo',
            'sent_at'    => now(),
        ]);

        $this->draft->update(['status' => 'sent']);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendEmailJob failed', [
            'draft_id' => $this->draft->id,
            'error'    => $e->getMessage(),
        ]);

        $this->draft->update(['status' => 'failed', 'error' => $e->getMessage()]);

        \App\Models\User::find($this->userId)?->notify(
            new DraftFailedNotification($this->draft->lead, $e->getMessage())
        );
    }
}
