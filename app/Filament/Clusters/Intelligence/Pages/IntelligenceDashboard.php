<?php

namespace App\Filament\Clusters\Intelligence\Pages;

use App\Filament\Clusters\Intelligence;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\LeadWebsiteAnalysis;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class IntelligenceDashboard extends Page
{
    protected static ?string $cluster = Intelligence::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.clusters.intelligence.pages.intelligence-dashboard';

    protected static ?string $slug = 'dashboard';

    public Lead $lead;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function getTitle(): string|Htmlable
    {
        return __('leads.intelligence_dashboard');
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.intelligence_dashboard');
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '{lead}/dashboard';
    }

    public function getProspectAnalysis(): ?LeadProspectAnalysis
    {
        return $this->lead->prospectAnalysis;
    }

    public function getWebsiteAnalysis(): ?LeadWebsiteAnalysis
    {
        return $this->lead->websiteAnalysis;
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.leads.view', $this->lead) => $this->lead->title,
            __('leads.intelligence_dashboard'),
        ];
    }
}
