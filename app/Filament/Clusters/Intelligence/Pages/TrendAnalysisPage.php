<?php

namespace App\Filament\Clusters\Intelligence\Pages;

use App\Filament\Clusters\Intelligence;
use App\Jobs\RunTrendAnalysisJob;
use App\Models\Lead;
use App\Models\LeadTrendAnalysis;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Html;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class TrendAnalysisPage extends Page
{
    protected static ?string $cluster = Intelligence::class;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.clusters.intelligence.pages.trend-analysis-page';

    protected static ?string $slug = 'trend-analysis';

    public Lead $lead;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function getTitle(): string|Htmlable
    {
        return __('leads.trend_analysis');
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.trend_analysis');
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '{lead}/trend-analysis';
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
                ->url(fn () => route('intelligence.analysis.pdf', ['lead' => $this->lead->id, 'type' => 'trend']))
                ->openUrlInNewTab()
                ->visible(fn () => $this->lead->trendAnalysis?->status === 'completed'),

            Action::make('download_docx')
                ->label(__('leads.download_docx'))
                ->icon(Heroicon::DocumentText)
                ->color('gray')
                ->url(fn () => route('intelligence.analysis.docx', ['lead' => $this->lead->id, 'type' => 'trend']))
                ->openUrlInNewTab()
                ->visible(fn () => $this->lead->trendAnalysis?->status === 'completed'),

            Action::make('run_analysis')
                ->label(__('leads.run_analysis'))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('info')
                ->fillForm(fn (): array => [
                    'topic' => trim(implode(' ', array_filter([
                        $this->lead->title,
                        $this->lead->category,
                    ]))),
                ])
                ->schema(function (): array {
                    $suggestionsHtml = $this->buildSuggestionsHtml();

                    return [
                        TextInput::make('topic')
                            ->label(__('leads.trend_analysis_topic_placeholder'))
                            ->required(),
                        Html::make($suggestionsHtml),
                    ];
                })
                ->action(function (array $data): void {
                    LeadTrendAnalysis::updateOrCreate(
                        ['lead_id' => $this->lead->id],
                        [
                            'topic' => $data['topic'],
                            'status' => LeadTrendAnalysis::STATUS_PENDING,
                            'raw_data' => null,
                            'result' => null,
                            'error_message' => null,
                            'started_at' => now(),
                            'completed_at' => null,
                        ],
                    );

                    RunTrendAnalysisJob::dispatch($this->lead, auth()->id());

                    Notification::make()
                        ->title(__('leads.trend_analysis_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }

    private function buildSuggestionsHtml(): HtmlString
    {
        $suggestions = $this->getSuggestedTopics();

        if (empty($suggestions)) {
            return new HtmlString('');
        }

        $chips = collect($suggestions)
            ->map(function (string $suggestion): string {
                $escaped = e($suggestion);
                $jsSafeValue = "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $suggestion)."'";

                return "<button type=\"button\" x-on:click=\"\$wire.set('mountedActions.0.data.topic', {$jsSafeValue})\" class=\"rounded-full border border-gray-300 bg-white px-2.5 py-1 text-xs text-gray-600 transition hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-500 dark:hover:bg-primary-900/30 dark:hover:text-primary-400\">{$escaped}</button>";
            })
            ->implode('');

        $label = e(__('leads.trend_analysis_suggestions'));

        return new HtmlString(
            "<div class=\"mt-1\"><p class=\"mb-2 text-xs text-gray-400 dark:text-gray-500\">{$label}</p><div class=\"flex flex-wrap gap-1.5\">{$chips}</div></div>"
        );
    }

    private function getSuggestedTopics(): array
    {
        $suggestions = [];

        if (filled($this->lead->title)) {
            $suggestions[] = $this->lead->title.' market trends';
            $suggestions[] = $this->lead->title.' competitors';
        }

        if (filled($this->lead->category)) {
            $suggestions[] = $this->lead->category.' industry trends';
            $suggestions[] = $this->lead->category.' market analysis';
        }

        if (filled($this->lead->address)) {
            $parts = array_filter(array_map('trim', explode(',', $this->lead->address)));
            $location = end($parts) ?: null;
            if ($location && filled($this->lead->category)) {
                $suggestions[] = $this->lead->category.' market in '.$location;
            }
        }

        if (filled($this->lead->title)) {
            $suggestions[] = $this->lead->title.' growth opportunities';
        }

        return array_values(array_unique(array_slice($suggestions, 0, 6)));
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.leads.view', $this->lead) => $this->lead->title,
            IntelligenceDashboard::getUrl(['lead' => $this->lead->id]) => __('leads.intelligence_nav_label'),
            __('leads.trend_analysis'),
        ];
    }
}
