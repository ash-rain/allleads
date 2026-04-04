<div wire:poll.5s="reload">
    @if (!$analysis)
        {{-- Empty state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 py-12 text-center dark:border-gray-600 dark:bg-gray-800/40">
            <x-heroicon-o-arrow-trending-up class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('leads.no_analysis_yet') }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('leads.trend_analysis_no_data_hint') }}</p>

            <div class="mt-4 flex w-full max-w-sm flex-col gap-2 px-6">
                <input wire:model="topic" type="text" placeholder="{{ __('leads.trend_analysis_topic_placeholder') }}"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-500" />
                @if (!empty($suggestions))
                    <div class="flex flex-wrap gap-1.5">
                        <p class="w-full text-xs text-gray-400 dark:text-gray-500">
                            {{ __('leads.trend_analysis_suggestions') }}</p>
                        @foreach ($suggestions as $suggestion)
                            @php $jsSafe = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $suggestion) . "'"; @endphp
                            <button type="button" x-on:click="$wire.set('topic', {{ $jsSafe }})"
                                class="rounded-full border border-gray-300 bg-white px-2.5 py-1 text-xs text-gray-600 transition hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-500 dark:hover:bg-primary-900/30 dark:hover:text-primary-400">
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                @endif
                <button wire:click="runAnalysis" wire:loading.attr="disabled" wire:target="runAnalysis"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="runAnalysis" class="flex items-center gap-1.5">
                        <x-heroicon-o-play class="h-4 w-4" />
                        {{ __('leads.run_analysis') }}
                    </span>
                    <span wire:loading wire:target="runAnalysis" class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ __('leads.trend_analysis_queued') }}
                    </span>
                </button>
            </div>
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
            @if ($analysis->topic)
                <p class="mt-2 text-xs font-medium text-blue-600 dark:text-blue-400">
                    {{ __('leads.trend_analysis_researching', ['topic' => $analysis->topic]) }}
                </p>
            @endif
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
            <button wire:click="retry" wire:loading.attr="disabled" wire:target="retry"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="retry" class="flex items-center gap-1.5">
                    <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                    {{ __('leads.retry_analysis') }}
                </span>
                <span wire:loading wire:target="retry" class="flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                    {{ __('leads.trend_analysis_queued') }}
                </span>
            </button>
        </div>
    @else
        {{-- Completed state --}}
        <div class="space-y-5">

            <x-trend-analysis-result :analysis="$analysis" />

            {{-- Research a different topic --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('leads.trend_analysis_research_new') }}
                </h3>
                <div class="flex gap-2">
                    <input wire:model="topic" type="text"
                        placeholder="{{ __('leads.trend_analysis_topic_placeholder') }}"
                        class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-500" />
                    <button wire:click="runAnalysis" wire:loading.attr="disabled" wire:target="runAnalysis"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="runAnalysis" class="flex items-center gap-1.5">
                            <x-heroicon-o-arrow-path class="h-4 w-4" />
                            {{ __('leads.retry_analysis') }}
                        </span>
                        <span wire:loading wire:target="runAnalysis" class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('leads.trend_analysis_queued') }}
                        </span>
                    </button>
                </div>
            </div>

            {{-- Meta --}}
            <p class="text-right text-xs text-gray-400">
                {{ __('leads.website_analysis_completed_at', ['date' => $analysis->completed_at?->diffForHumans() ?? '—']) }}
            </p>

        </div>
    @endif
</div>
