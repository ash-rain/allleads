<div wire:poll.5s="reload">
    @if (!$analysis)
        {{-- Empty state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 py-12 text-center dark:border-gray-600 dark:bg-gray-800/40">
            <x-heroicon-o-globe-alt class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('leads.no_analysis_yet') }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('leads.website_analysis_no_data_hint') }}</p>
            <button wire:click="runAnalysis"
                class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <x-heroicon-o-play class="h-4 w-4" />
                {{ __('leads.run_analysis') }}
            </button>
        </div>
    @elseif ($analysis->status === 'pending')
        {{-- Pending state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-blue-200 bg-blue-50 py-12 text-center dark:border-blue-800 dark:bg-blue-900/20">
            <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">{{ __('leads.analysis_in_progress') }}</p>
            <p class="mt-1 text-xs text-blue-500">{{ __('leads.analysis_pending_hint') }}</p>
        </div>
    @elseif ($analysis->status === 'failed')
        {{-- Failed state --}}
        <div class="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-900/20">
            <div class="mb-4 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-500" />
                <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ __('leads.analysis_failed') }}</p>
            </div>
            @if ($analysis->error_message)
                <p
                    class="mb-4 rounded bg-red-100 p-3 font-mono text-xs text-red-600 dark:bg-red-900/40 dark:text-red-300">
                    {{ $analysis->error_message }}
                </p>
            @endif
            <button wire:click="retry"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                {{ __('leads.retry_analysis') }}
            </button>
        </div>
    @else
        {{-- Completed state --}}
        @php $result = $analysis->result ?? []; @endphp

        <div class="space-y-5">

            {{-- Score badge --}}
            @if (isset($result['overall_score']))
                <div class="flex items-center gap-3">
                    @php
                        $score = (int) $result['overall_score'];
                        $scoreColor =
                            $score >= 75
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                : ($score >= 50
                                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300');
                    @endphp
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold {{ $scoreColor }}">
                        <x-heroicon-o-star class="h-4 w-4" />
                        {{ __('leads.website_analysis_overall_score') }}: {{ $score }}/100
                    </span>
                    <span class="text-xs text-gray-400">
                        {{ __('leads.analysis_analysed_with', ['model' => $analysis->model ?? '—']) }}
                    </span>
                </div>
            @endif

            {{-- Business Overview --}}
            @if (!empty($result['business_overview']))
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('leads.website_analysis_business_overview') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['business_overview'] }}</p>
                </div>
            @endif

            {{-- Value Proposition --}}
            @if (!empty($result['value_proposition']))
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('leads.website_analysis_value_proposition') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['value_proposition'] }}</p>
                </div>
            @endif

            {{-- Sales Angles --}}
            @if (!empty($result['sales_angles']))
                <div
                    class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">
                        {{ __('leads.website_analysis_sales_angles') }}
                    </h3>
                    <ul class="space-y-1.5">
                        @foreach ((array) $result['sales_angles'] as $angle)
                            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <x-heroicon-o-arrow-right
                                    class="mt-0.5 h-4 w-4 shrink-0 text-green-500 dark:text-green-400" />
                                {{ $angle }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Pain Points --}}
            @if (!empty($result['pain_points']))
                <div
                    class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-orange-600 dark:text-orange-400">
                        {{ __('leads.website_analysis_pain_points') }}
                    </h3>
                    <ul class="space-y-1.5">
                        @foreach ((array) $result['pain_points'] as $point)
                            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <x-heroicon-o-exclamation-circle
                                    class="mt-0.5 h-4 w-4 shrink-0 text-orange-500 dark:text-orange-400" />
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Competitive Position + Growth Signals --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if (!empty($result['competitive_position']))
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('leads.website_analysis_competitive_position') }}
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['competitive_position'] }}</p>
                    </div>
                @endif

                @if (!empty($result['growth_signals']))
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('leads.website_analysis_growth_signals') }}
                        </h3>
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['growth_signals'] }}</p>
                    </div>
                @endif
            </div>

            {{-- Tech Stack --}}
            @if (!empty($analysis->scraped_data['tech_stack']))
                <div
                    class="rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-purple-600 dark:text-purple-400">
                        {{ __('leads.website_analysis_tech_stack') }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($analysis->scraped_data['tech_stack'] as $tech)
                            <span
                                class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/60 dark:text-purple-300">{{ $tech }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Collapsible: Raw Scraped Data --}}
            @if ($analysis->scraped_data)
                <details class="rounded-xl border border-gray-200 dark:border-gray-700">
                    <summary
                        class="cursor-pointer rounded-xl px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/60">
                        {{ __('leads.website_analysis_scraped_data') }}
                    </summary>
                    <div class="px-4 pb-4">
                        <pre
                            class="max-h-64 overflow-auto rounded bg-gray-100 p-3 font-mono text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($analysis->scraped_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </details>
            @endif

            {{-- Meta --}}
            <p class="text-right text-xs text-gray-400">
                {{ __('leads.website_analysis_completed_at', ['date' => $analysis->completed_at?->diffForHumans() ?? '—']) }}
            </p>

        </div>
    @endif
</div>
