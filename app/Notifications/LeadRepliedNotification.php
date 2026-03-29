<?php

namespace App\Notifications;

use App\Models\EmailThread;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeadRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Lead $lead,
        public readonly EmailThread $thread,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.lead_replied_title'),
            'body' => __('notifications.lead_replied_body', ['lead' => $this->lead->title]),
            'lead_id' => $this->lead->id,
            'thread_id' => $this->thread->id,
            'url' => '/app/leads/' . $this->lead->id,
        ];
    }
}
