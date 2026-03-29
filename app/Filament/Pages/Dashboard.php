<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EmailFunnelChartWidget;
use App\Filament\Widgets\EmailsSentTodayWidget;
use App\Filament\Widgets\LeadsImportedChartWidget;
use App\Filament\Widgets\OpenThreadsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\RepliesReceivedWidget;
use App\Filament\Widgets\TotalLeadsWidget;
use App\Filament\Widgets\WebDevProspectsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int    $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            TotalLeadsWidget::class,
            WebDevProspectsWidget::class,
            EmailsSentTodayWidget::class,
            OpenThreadsWidget::class,
            RepliesReceivedWidget::class,
            LeadsImportedChartWidget::class,
            EmailFunnelChartWidget::class,
            RecentActivityWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'xl'      => 4,
        ];
    }
}
