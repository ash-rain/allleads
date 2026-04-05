<x-filament-panels::page>
    <div wire:poll.5s>
        {{-- Tabs --}}
        <div class="mb-4 flex items-center gap-1 border-b border-gray-200 dark:border-gray-700">
            <button wire:click="setTab('active')"
                class="rounded-t px-4 py-2 text-sm font-medium transition-colors {{ $activeTab === 'active' ? 'border-b-2 border-primary-600 text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ __('leads.trend_analysis_tab_active') }}
            </button>
            <button wire:click="setTab('archived')"
                class="flex items-center gap-1.5 rounded-t px-4 py-2 text-sm font-medium transition-colors {{ $activeTab === 'archived' ? 'border-b-2 border-primary-600 text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ __('leads.trend_analysis_tab_archived') }}
                @if ($archivedCount > 0)
                    <span
                        class="rounded-full bg-gray-200 px-1.5 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $archivedCount }}</span>
                @endif
            </button>
        </div>

        @if ($analyses->isEmpty())
            <div
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 py-16 text-center dark:border-gray-600 dark:bg-gray-800/40">
                <x-heroicon-o-signal class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">
                    @if ($activeTab === 'archived')
                        {{ __('leads.trend_analysis_archive_empty') }}
                    @else
                        {{ __('leads.geo_analysis_company_empty_title') }}
                    @endif
                </h3>
                <p class="mt-2 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                    @if ($activeTab !== 'archived')
                        {{ __('leads.geo_analysis_company_empty_hint') }}
                    @endif
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($analyses as $analysis)
                    @php
                        $isFailed = $analysis->status === 'failed';
                        $isArchived = !is_null($analysis->archived_at);
                        $result = $analysis->result ?? [];
                    @endphp
                    <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }"
                        class="rounded-xl border {{ $isFailed ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-5 shadow-sm">

                        {{-- Card header --}}
                        <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                            <button @click="open = !open" class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                @if ($analysis->status === 'pending')
                                    <svg class="h-5 w-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                @elseif ($isFailed)
                                    <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-500" />
                                @elseif ($isArchived)
                                    <x-heroicon-o-archive-box class="h-5 w-5 text-gray-400" />
                                @else
                                    <x-heroicon-o-check-circle class="h-5 w-5 text-green-500" />
                                @endif
                                <h3 class="flex-1 font-semibold text-gray-900 dark:text-white">
                                    {{ $analysis->url }}
                                </h3>
                                <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                    x-bind:class="open ? 'rotate-180' : ''" />
                            </button>
                            <div class="flex shrink-0 items-center gap-2">
                                @php
                                    $statusColor = match ($analysis->status) {
                                        'completed'
                                            => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                        'pending'
                                            => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                        'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ __('leads.analysis_status_' . $analysis->status) }}
                                </span>
                                @if ($analysis->completed_at)
                                    <span
                                        class="text-xs text-gray-400">{{ $analysis->completed_at->diffForHumans() }}</span>
                                @endif

                                {{-- Action buttons --}}
                                @if ($isFailed)
                                    <button wire:click="deleteAnalysis({{ $analysis->id }})"
                                        wire:confirm="{{ __('leads.geo_analysis_delete_confirm') }}"
                                        class="rounded p-1 text-gray-400 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/40 dark:hover:text-red-400"
                                        title="{{ __('leads.geo_analysis_delete') }}">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                @elseif ($analysis->status === 'completed')
                                    @if ($isArchived)
                                        <button wire:click="unarchiveAnalysis({{ $analysis->id }})"
                                            class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                            title="{{ __('leads.trend_analysis_unarchive') }}">
                                            <x-heroicon-o-arrow-uturn-up class="h-4 w-4" />
                                        </button>
                                    @else
                                        <button wire:click="archiveAnalysis({{ $analysis->id }})"
                                            class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                            title="{{ __('leads.trend_analysis_archive') }}">
                                            <x-heroicon-o-archive-box-arrow-down class="h-4 w-4" />
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Failed error message --}}
                        @if ($isFailed && $analysis->error_message)
                            <p
                                class="mb-3 rounded bg-red-100 p-2 font-mono text-xs text-red-600 dark:bg-red-900/40 dark:text-red-300">
                                {{ $analysis->error_message }}
                            </p>
                        @endif

                        {{-- Completed result --}}
                        <div x-show="open" x-transition>
                            @if ($analysis->status === 'completed' && !empty($result))
                                <x-geo-analysis-result :analysis="$analysis" />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
