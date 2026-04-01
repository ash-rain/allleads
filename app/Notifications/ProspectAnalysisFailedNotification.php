<?php

namespace App\Notifications;

use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
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

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->danger()
            ->title(__('notifications.prospect_analysis_failed_title'))
            ->body(__('notifications.prospect_analysis_failed_body', [
                'lead' => $this->lead->title,
                'error' => $this->error,
            ]))
            ->actions([
                Action::make('view')
                    ->label(__('notifications.view_lead'))
                    ->url('/app/leads/'.$this->lead->id),
            ])
            ->getDatabaseMessage();
    }
}
