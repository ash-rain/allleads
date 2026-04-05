<?php

namespace App\Filament\Clusters\Intelligence\Pages;

use App\Filament\Clusters\Intelligence;
use App\Jobs\RunWebsiteAnalysisJob;
use App\Models\Lead;
use App\Models\LeadWebsiteAnalysis;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class WebsiteAnalysisPage extends Page
{
    protected static ?string $cluster = Intelligence::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.clusters.intelligence.pages.website-analysis-page';

    protected static ?string $slug = 'website-analysis';

    public Lead $lead;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function getTitle(): string|Htmlable
    {
        return __('leads.website_analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.website_analysis');
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '{lead}/website-analysis';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_dashboard')
                ->label(__('leads.intelligence_dashboard'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(IntelligenceDashboard::getUrl(['lead' => $this->lead->id])),

            Action::make('download_pdf')
                ->label(__('leads.download_pdf'))
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->url(fn () => route('intelligence.analysis.pdf', ['lead' => $this->lead->id, 'type' => 'website']))
                ->openUrlInNewTab()
                ->visible(fn () => $this->lead->websiteAnalysis?->status === 'completed'),

            Action::make('download_docx')
                ->label(__('leads.download_docx'))
                ->icon(Heroicon::DocumentText)
                ->color('gray')
                ->url(fn () => route('intelligence.analysis.docx', ['lead' => $this->lead->id, 'type' => 'website']))
                ->openUrlInNewTab()
                ->visible(fn () => $this->lead->websiteAnalysis?->status === 'completed'),

            Action::make('run_analysis')
                ->label(__('leads.run_analysis'))
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->requiresConfirmation()
                ->action(function (): void {
                    LeadWebsiteAnalysis::updateOrCreate(
                        ['lead_id' => $this->lead->id],
                        [
                            'status' => LeadWebsiteAnalysis::STATUS_PENDING,
                            'scraped_data' => null,
                            'result' => null,
                            'error_message' => null,
                            'started_at' => now(),
                            'completed_at' => null,
                        ],
                    );

                    RunWebsiteAnalysisJob::dispatch($this->lead, auth()->id());

                    Notification::make()
                        ->title(__('leads.website_analysis_queued'))
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
            __('leads.website_analysis'),
        ];
    }
}
