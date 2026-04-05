<div wire:poll.5s="reload">
    @if (!$analysis)
        {{-- Empty state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 py-12 text-center dark:border-gray-600 dark:bg-gray-800/40">
            <x-heroicon-o-signal class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('leads.no_analysis_yet') }}</p>
            <p class="mt-1 text-xs text-gray-400">
                @if ($lead?->website)
                    {{ __('leads.geo_analysis_no_data_hint') }}
                @else
                    {{ __('leads.geo_analysis_no_website_hint') }}
                @endif
            </p>
            <div class="mt-4">
                <button wire:click="runAnalysis" wire:loading.attr="disabled" wire:target="runAnalysis"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-60">
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
                        {{ __('leads.geo_analysis_queued') }}
                    </span>
                </button>
            </div>
        </div>
    @elseif ($analysis->status === 'pending')
        {{-- Pending state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-amber-200 bg-amber-50 py-12 text-center dark:border-amber-800 dark:bg-amber-900/20">
            <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-amber-500" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('leads.analysis_in_progress') }}</p>
            <p class="mt-1 text-xs text-amber-500">{{ __('leads.analysis_pending_hint') }}</p>
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
                    {{ __('leads.geo_analysis_queued') }}
                </span>
            </button>
        </div>
    @else
        {{-- Completed state --}}
        <div class="space-y-5">

            <x-geo-analysis-result :analysis="$analysis" />

            {{-- Meta --}}
            <p class="text-right text-xs text-gray-400">
                {{ __('leads.website_analysis_completed_at', ['date' => $analysis->completed_at?->diffForHumans() ?? '—']) }}
            </p>

        </div>
    @endif
</div>
