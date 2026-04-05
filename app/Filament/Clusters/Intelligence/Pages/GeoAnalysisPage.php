<?php

namespace App\Filament\Clusters\Intelligence\Pages;

use App\Filament\Clusters\Intelligence;
use App\Jobs\RunGeoAnalysisJob;
use App\Models\Lead;
use App\Models\LeadGeoAnalysis;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class GeoAnalysisPage extends Page
{
    protected static ?string $cluster = Intelligence::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.clusters.intelligence.pages.geo-analysis-page';

    protected static ?string $slug = 'geo-analysis';

    public Lead $lead;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function getTitle(): string|Htmlable
    {
        return __('leads.geo_analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.geo_analysis');
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '{lead}/geo-analysis';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_dashboard')
                ->label(__('leads.intelligence_dashboard'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(IntelligenceDashboard::getUrl(['lead' => $this->lead->id])),

            Action::make('run_analysis')
                ->label(__('leads.run_analysis'))
                ->icon('heroicon-o-signal')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('leads.geo_analysis'))
                ->modalDescription(fn (): string => $this->lead->website
                    ? __('leads.geo_analysis_no_data_hint')
                    : __('leads.geo_analysis_no_website_hint'))
                ->action(function (): void {
                    LeadGeoAnalysis::updateOrCreate(
                        ['lead_id' => $this->lead->id],
                        [
                            'status' => LeadGeoAnalysis::STATUS_PENDING,
                            'raw_data' => null,
                            'result' => null,
                            'error_message' => null,
                            'started_at' => now(),
                            'completed_at' => null,
                        ],
                    );

                    RunGeoAnalysisJob::dispatch($this->lead, auth()->id());

                    Notification::make()
                        ->title(__('leads.geo_analysis_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
