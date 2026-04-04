<?php

namespace App\Filament\Pages;

use App\Jobs\RunCompanyTrendAnalysisJob;
use App\Models\BusinessSetting;
use App\Models\TrendAnalysis;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Html;
use Illuminate\Support\HtmlString;

class BusinessIntelligence extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-arrow-trending-up';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('common.nav_group_intelligence');
    }

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Trend Analysis';

    protected string $view = 'filament.pages.business-intelligence';

    public string $activeTab = 'active';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function deleteAnalysis(int $id): void
    {
        $analysis = TrendAnalysis::where('user_id', auth()->id())->findOrFail($id);
        $analysis->delete();

        Notification::make()
            ->title(__('leads.trend_analysis_deleted'))
            ->success()
            ->send();
    }

    public function archiveAnalysis(int $id): void
    {
        TrendAnalysis::where('user_id', auth()->id())
            ->findOrFail($id)
            ->update(['archived_at' => now()]);
    }

    public function unarchiveAnalysis(int $id): void
    {
        TrendAnalysis::where('user_id', auth()->id())
            ->findOrFail($id)
            ->update(['archived_at' => null]);
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
        $setting = BusinessSetting::singleton();
        $suggestions = [];

        if (filled($setting->industry)) {
            $suggestions[] = $setting->industry.' market trends';
            $suggestions[] = $setting->industry.' competitive landscape';
        }

        if (filled($setting->key_services)) {
            $services = array_slice(
                array_filter(array_map('trim', preg_split('/[,\n]+/', $setting->key_services))),
                0,
                2
            );
            foreach ($services as $service) {
                $suggestions[] = $service.' trends';
            }
        }

        if (filled($setting->target_audience) && filled($setting->geographic_focus)) {
            $suggestions[] = $setting->target_audience.' market in '.$setting->geographic_focus;
        } elseif (filled($setting->target_audience)) {
            $suggestions[] = $setting->target_audience.' trends';
        }

        if (filled($setting->common_pain_points)) {
            $painPoints = array_slice(
                array_filter(array_map('trim', preg_split('/[,\n]+/', $setting->common_pain_points))),
                0,
                1
            );
            foreach ($painPoints as $point) {
                $suggestions[] = $point;
            }
        }

        return array_values(array_unique(array_slice($suggestions, 0, 6)));
    }

    public function getViewData(): array
    {
        $query = TrendAnalysis::where('user_id', auth()->id())->latest();

        if ($this->activeTab === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        return [
            'analyses' => $query->limit(20)->get(),
            'archivedCount' => TrendAnalysis::where('user_id', auth()->id())
                ->whereNotNull('archived_at')
                ->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        $suggestionsHtml = $this->buildSuggestionsHtml();

        return [
            Action::make('run_trend_analysis')
                ->label(__('leads.trend_analysis_run_company'))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('primary')
                ->schema([
                    TextInput::make('topic')
                        ->label(__('leads.trend_analysis_topic'))
                        ->placeholder(__('leads.trend_analysis_topic_placeholder'))
                        ->required()
                        ->maxLength(255),

                    Html::make($suggestionsHtml),
                ])
                ->action(function (array $data): void {
                    RunCompanyTrendAnalysisJob::dispatch(
                        $data['topic'],
                        auth()->id()
                    );

                    Notification::make()
                        ->title(__('leads.trend_analysis_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
