<?php

namespace App\Filament\Widgets;

use App\Models\EmailMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailsSentTodayWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $today = EmailMessage::where('role', 'outbound')
            ->whereDate('sent_at', today())
            ->count();

        $total = EmailMessage::where('role', 'outbound')->count();

        return [
            Stat::make(__('common.emails_sent_today'), $today)
                ->description(__('common.total_sent', ['count' => $total]))
                ->descriptionIcon('heroicon-m-envelope')
                ->color('success'),
        ];
    }
}
