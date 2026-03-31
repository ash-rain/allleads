<?php

namespace App\Filament\Clusters\Intelligence\Pages;

use App\Filament\Clusters\Intelligence;
use App\Jobs\RunProspectAnalysisJob;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class ProspectAnalysisPage extends Page
{
    protected static ?string $cluster = Intelligence::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.clusters.intelligence.pages.prospect-analysis-page';

    protected static ?string $slug = 'prospect-analysis';

    public Lead $lead;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function getTitle(): string|Htmlable
    {
        return __('leads.prospect_analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.prospect_analysis');
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '{lead}/prospect-analysis';
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
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('leads.analysis_confirm_heading'))
                ->modalDescription(__('leads.analysis_confirm_body'))
                ->action(function (): void {
                    LeadProspectAnalysis::updateOrCreate(
                        ['lead_id' => $this->lead->id],
                        [
                            'status' => LeadProspectAnalysis::STATUS_PENDING,
                            'result' => null,
                            'error_message' => null,
                            'started_at' => now(),
                            'completed_at' => null,
                        ],
                    );

                    RunProspectAnalysisJob::dispatch($this->lead, auth()->id());

                    Notification::make()
                        ->title(__('leads.analysis_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.leads.view', $this->lead) => $this->lead->title,
            IntelligenceDashboard::getUrl(['lead' => $this->lead->id]) => __('leads.intelligence_nav_label'),
            __('leads.prospect_analysis'),
        ];
    }
}
