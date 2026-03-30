<?php

namespace App\Filament\Widgets;

use App\Models\EmailMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RepliesReceivedWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        $thisWeek = EmailMessage::where('role', 'lead_reply')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $total = EmailMessage::where('role', 'lead_reply')->count();

        return [
            Stat::make(__('common.replies_received'), $total)
                ->description("+{$thisWeek} ".__('common.this_week'))
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color('success'),
        ];
    }
}
