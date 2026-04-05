<?php

namespace App\Filament\Pages;

use App\Jobs\RunCompanyGeoAnalysisJob;
use App\Models\BusinessSetting;
use App\Models\GeoAnalysis;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BusinessGeoAnalysis extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-signal';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('common.nav_group_intelligence');
    }

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'GEO Analysis';

    protected string $view = 'filament.pages.business-geo-analysis';

    public string $activeTab = 'active';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function deleteAnalysis(int $id): void
    {
        GeoAnalysis::where('user_id', auth()->id())->findOrFail($id)->delete();

        Notification::make()
            ->title(__('leads.geo_analysis_deleted'))
            ->success()
            ->send();
    }

    public function archiveAnalysis(int $id): void
    {
        GeoAnalysis::where('user_id', auth()->id())
            ->findOrFail($id)
            ->update(['archived_at' => now()]);
    }

    public function unarchiveAnalysis(int $id): void
    {
        GeoAnalysis::where('user_id', auth()->id())
            ->findOrFail($id)
            ->update(['archived_at' => null]);
    }

    public function getViewData(): array
    {
        $query = GeoAnalysis::where('user_id', auth()->id())->latest();

        if ($this->activeTab === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        return [
            'analyses' => $query->limit(20)->get(),
            'archivedCount' => GeoAnalysis::where('user_id', auth()->id())
                ->whereNotNull('archived_at')
                ->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        $setting = BusinessSetting::singleton();
        $websiteUrl = $setting->website_url ?? '';

        return [
            Action::make('run_geo_analysis')
                ->label(__('leads.geo_analysis_run_company'))
                ->icon('heroicon-o-signal')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('leads.geo_analysis_run_company'))
                ->modalDescription($websiteUrl
                    ? __('leads.geo_analysis_company_modal_description', ['url' => $websiteUrl])
                    : __('leads.geo_analysis_company_no_url'))
                ->disabled(! $websiteUrl)
                ->action(function () use ($websiteUrl): void {
                    RunCompanyGeoAnalysisJob::dispatch($websiteUrl, auth()->id());

                    Notification::make()
                        ->title(__('leads.geo_analysis_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
