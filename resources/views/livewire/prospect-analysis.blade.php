<div {{ $isPending ? 'wire:poll.5s="reload"' : '' }}>
    @if (!$analysis)
        {{-- Empty state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 py-12 text-center dark:border-gray-600 dark:bg-gray-800/40">
            <x-heroicon-o-cpu-chip class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('leads.analysis_no_data') }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('leads.analysis_no_data_hint') }}</p>
        </div>
    @elseif ($analysis->status === 'pending')
        {{-- Pending state --}}
        <div
            class="flex flex-col items-center justify-center rounded-xl border border-blue-200 bg-blue-50 py-12 text-center dark:border-blue-800 dark:bg-blue-900/20">
            <svg class="mx-auto mb-3 h-8 w-8 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">{{ __('leads.analysis_pending') }}</p>
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
                {{ __('leads.analysis_retry') }}
            </button>
        </div>
    @else
        {{-- Completed state --}}
        @php $result = $analysis->result ?? []; @endphp

        <div class="space-y-5">

            {{-- Score badge --}}
            @if (isset($result['prospect_score']))
                <div class="flex items-center gap-3">
                    @php
                        $score = (int) $result['prospect_score'];
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
                        {{ __('leads.analysis_score') }}: {{ $score }}/100
                    </span>
                    <span class="text-xs text-gray-400">
                        {{ __('leads.analysis_analysed_with', ['model' => $analysis->model ?? '—']) }}
                    </span>
                </div>
            @endif

            {{-- Company Fit --}}
            @if (!empty($result['company_fit']))
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('leads.analysis_company_fit') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['company_fit'] }}</p>
                </div>
            @endif

            {{-- Contact Intel --}}
            @if (!empty($result['contact_intel']))
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('leads.analysis_contact_intel') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['contact_intel'] }}</p>
                </div>
            @endif

            {{-- Opportunity --}}
            @if (!empty($result['opportunity']))
                <div
                    class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">
                        {{ __('leads.analysis_opportunity') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['opportunity'] }}</p>
                </div>
            @endif

            {{-- Competitive Intel --}}
            @if (!empty($result['competitive_intel']))
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('leads.analysis_competitive_intel') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['competitive_intel'] }}</p>
                </div>
            @endif

            {{-- Outreach Strategy --}}
            @if (!empty($result['outreach_strategy']))
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">
                        {{ __('leads.analysis_outreach_strategy') }}
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ $result['outreach_strategy'] }}</p>
                </div>
            @endif

            {{-- Meta --}}
            <p class="text-right text-xs text-gray-400">
                {{ __('leads.analysis_completed_at', ['date' => $analysis->completed_at?->diffForHumans() ?? '—']) }}
            </p>

        </div>
    @endif
</div>
