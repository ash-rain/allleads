<?php

namespace App\Notifications;

use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ImportBatch $batch,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.import_completed_title'),
            'body' => __('notifications.import_completed_body', [
                'filename' => $this->batch->filename,
                'created' => $this->batch->created_count ?? 0,
                'updated' => $this->batch->updated_count ?? 0,
                'skipped' => $this->batch->skipped_count ?? 0,
                'failed' => $this->batch->failed_count ?? 0,
            ]),
            'batch_id' => $this->batch->id,
            'url' => '/admin/import-batches/' . $this->batch->id,
        ];
    }
}
