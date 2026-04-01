<?php

namespace App\Notifications;

use App\Models\EmailThread;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
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

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->info()
            ->title(__('notifications.lead_replied_title'))
            ->body(__('notifications.lead_replied_body', ['lead' => $this->lead->title]))
            ->actions([
                Action::make('view')
                    ->label(__('notifications.view_lead'))
                    ->url('/app/leads/'.$this->lead->id),
            ])
            ->getDatabaseMessage();
    }
}
