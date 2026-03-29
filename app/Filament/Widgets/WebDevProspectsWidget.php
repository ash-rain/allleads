<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WebDevProspectsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $prospects = Lead::where(function ($q): void {
            $q->where('review_rating', '>=', 4.0)
              ->whereNull('website');
        })->count();

        return [
            Stat::make(__('leads.web_dev_prospects'), $prospects)
                ->description(__('leads.high_rating_no_website'))
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
