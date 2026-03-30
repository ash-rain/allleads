<?php

namespace App\Filament\Widgets;

use App\Models\EmailThread;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenThreadsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        $open = EmailThread::where('status', 'open')->count();

        return [
            Stat::make(__('common.open_threads'), $open)
                ->description(__('common.awaiting_reply'))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),
        ];
    }
}
