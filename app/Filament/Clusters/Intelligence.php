<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class Intelligence extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('leads.intelligence_nav_label');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('leads.intelligence_nav_label');
    }

    public function getSubNavigation(): array
    {
        return [];
    }
}
