<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalLeadsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total    = Lead::count();
        $thisWeek = Lead::where('created_at', '>=', now()->startOfWeek())->count();

        return [
            Stat::make(__('common.total_leads'), $total)
                ->description("+{$thisWeek} " . __('common.this_week'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
        ];
    }
}
