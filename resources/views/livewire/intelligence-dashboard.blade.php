@php
    $prospectScore = $prospectAnalysis?->result['prospect_score'] ?? null;
    $websiteScore = $websiteAnalysis?->result['overall_score'] ?? null;
    $trendScore = $trendAnalysis?->result['relevance_score'] ?? null;
    $geoScore = $geoAnalysis?->result['geo_score'] ?? null;
@endphp

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

    {{-- Prospect Analysis card --}}
    <a href="{{ \App\Filament\Clusters\Intelligence\Pages\ProspectAnalysisPage::getUrl(['lead' => $lead->id]) }}"
        class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                    <x-heroicon-o-cpu-chip class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('leads.prospect_analysis') }}</h3>
            </div>
            @if ($prospectAnalysis)
                @php
                    $statusColor = match ($prospectAnalysis->status) {
                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                    {{ __('leads.analysis_status_' . $prospectAnalysis->status) }}
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">AI analysis of lead data, scoring, and outreach strategy.</p>
        @if ($prospectScore !== null)
            <p class="mt-3 text-sm font-semibold text-blue-600 dark:text-blue-400">
                {{ __('leads.analysis_score_label', ['score' => $prospectScore . '/100']) }}
            </p>
        @endif
        @if ($prospectAnalysis?->completed_at)
            <p class="mt-1 text-xs text-gray-400">
                {{ __('leads.analysis_last_run', ['date' => $prospectAnalysis->completed_at->diffForHumans()]) }}
            </p>
        @endif
    </a>

    {{-- Website Analysis card --}}
    <a href="{{ \App\Filament\Clusters\Intelligence\Pages\WebsiteAnalysisPage::getUrl(['lead' => $lead->id]) }}"
        class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                    <x-heroicon-o-globe-alt class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('leads.website_analysis') }}</h3>
            </div>
            @if ($websiteAnalysis)
                @php
                    $statusColor = match ($websiteAnalysis->status) {
                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                    {{ __('leads.analysis_status_' . $websiteAnalysis->status) }}
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Deep website scraping with AI business intelligence and
            sales angles.</p>
        @if ($websiteScore !== null)
            <p class="mt-3 text-sm font-semibold text-purple-600 dark:text-purple-400">
                {{ __('leads.analysis_score_label', ['score' => $websiteScore . '/100']) }}
            </p>
        @endif
        @if ($websiteAnalysis?->completed_at)
            <p class="mt-1 text-xs text-gray-400">
                {{ __('leads.analysis_last_run', ['date' => $websiteAnalysis->completed_at->diffForHumans()]) }}
            </p>
        @endif
    </a>

    {{-- Trend Analysis card --}}
    <a href="{{ \App\Filament\Clusters\Intelligence\Pages\TrendAnalysisPage::getUrl(['lead' => $lead->id]) }}"
        class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-900/30">
                    <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('leads.trend_analysis') }}</h3>
            </div>
            @if ($trendAnalysis)
                @php
                    $statusColor = match ($trendAnalysis->status) {
                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                    {{ __('leads.analysis_status_' . $trendAnalysis->status) }}
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Reddit, Hacker News & prediction market signals for this
            lead's industry.</p>
        @if ($trendScore !== null)
            <p class="mt-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                {{ __('leads.analysis_score_label', ['score' => $trendScore . '/100']) }}
            </p>
        @endif
        @if ($trendAnalysis?->completed_at)
            <p class="mt-1 text-xs text-gray-400">
                {{ __('leads.analysis_last_run', ['date' => $trendAnalysis->completed_at->diffForHumans()]) }}
            </p>
        @endif
    </a>

    {{-- GEO Analysis card --}}
    <a href="{{ \App\Filament\Clusters\Intelligence\Pages\GeoAnalysisPage::getUrl(['lead' => $lead->id]) }}"
        class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-amber-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30">
                    <x-heroicon-o-signal class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('leads.geo_analysis') }}</h3>
            </div>
            @if ($geoAnalysis)
                @php
                    $statusColor = match ($geoAnalysis->status) {
                        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                    {{ __('leads.analysis_status_' . $geoAnalysis->status) }}
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">AI crawler access, citability scoring &amp; brand authority for generative search.</p>
        @if ($geoScore !== null)
            <p class="mt-3 text-sm font-semibold text-amber-600 dark:text-amber-400">
                {{ __('leads.analysis_score_label', ['score' => $geoScore . '/100']) }}
            </p>
        @endif
        @if ($geoAnalysis?->completed_at)
            <p class="mt-1 text-xs text-gray-400">
                {{ __('leads.analysis_last_run', ['date' => $geoAnalysis->completed_at->diffForHumans()]) }}
            </p>
        @endif
    </a>

    {{-- Future: Competitor Analysis (placeholder) --}}
    <div
        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 opacity-60 dark:border-gray-600 dark:bg-gray-800/40">
        <div class="mb-4 flex items-center gap-3">
            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                <x-heroicon-o-chart-bar class="h-6 w-6 text-gray-400" />
            </div>
            <h3 class="font-semibold text-gray-400 dark:text-gray-500">Competitor Analysis</h3>
        </div>
        <p class="text-sm text-gray-400 dark:text-gray-500">Coming soon — identify and analyse key competitors.</p>
    </div>

</div>
