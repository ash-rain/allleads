<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProspectAnalysisFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Lead $lead,
        public readonly string $error,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.prospect_analysis_failed_title'),
            'body' => __('notifications.prospect_analysis_failed_body', [
                'lead' => $this->lead->title,
                'error' => $this->error,
            ]),
            'lead_id' => $this->lead->id,
            'url' => '/app/leads/' . $this->lead->id,
        ];
    }
}
