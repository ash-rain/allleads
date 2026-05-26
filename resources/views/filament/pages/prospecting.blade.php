<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Search Form --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('prospecting.new_search') }}</x-slot>

            <form wire:submit="startSearch" class="space-y-4">
                {{ $this->searchForm }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                        {{ __('prospecting.search') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Recent Sessions --}}
        @if($this->getRecentSessions()->isNotEmpty() && !$this->activeSessionId)
            <x-filament::section>
                <x-slot name="heading">{{ __('prospecting.recent_sessions') }}</x-slot>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->getRecentSessions() as $session)
                        <div class="flex items-center justify-between py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 px-2 rounded"
                             wire:click="loadSession({{ $session->id }})">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $session->search_query }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('prospecting.in_territory', ['territory' => $session->territory->name]) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-filament::badge :color="match($session->status) {
                                    'pending' => 'gray',
                                    'searching' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                }">
                                    {{ $session->status }}
                                </x-filament::badge>
                                <span class="text-sm text-gray-500">
                                    {{ $session->result_count }} {{ __('prospecting.results') }}
                                    · {{ $session->imported_count }} {{ __('prospecting.imported') }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $session->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Active Session Results --}}
        @if($this->activeSessionId)
            @php
                $session = $this->getActiveSession();
                $results = $this->getResults();
            @endphp

            @if($session)
                {{-- Session Header --}}
                <x-filament::section>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                "{{ $session->search_query }}" {{ __('prospecting.in_territory', ['territory' => $session->territory->name]) }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $session->result_count }} {{ __('prospecting.results_found') }}
                                @if($session->sources_used)
                                    · {{ __('prospecting.sources') }}: {{ implode(', ', $session->sources_used) }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <x-filament::badge :color="match($session->status) {
                                'pending' => 'gray',
                                'searching' => 'info',
                                'completed' => 'success',
                                'failed' => 'danger',
                            }" size="lg">
                                {{ $session->status }}
                            </x-filament::badge>
                        </div>
                    </div>
                </x-filament::section>

                @if($session->status === 'pending' || $session->isSearching())
                    <div class="flex items-center justify-center py-12" wire:poll.2s>
                        <div class="text-center">
                            <x-filament::loading-indicator class="mx-auto h-8 w-8" />
                            <p class="mt-2 text-gray-500 dark:text-gray-400">
                                {{ $session->status === 'pending' ? __('prospecting.queued') : __('prospecting.searching') }}
                            </p>
                        </div>
                    </div>
                @elseif($session->isCompleted() && $results->isNotEmpty())
                    {{-- Split Panel: Map + Results List --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- Map Panel --}}
                        <x-filament::section>
                            <div
                                id="prospecting-map"
                                class="h-[600px] w-full rounded-lg"
                                wire:key="map-{{ $this->activeSessionId }}"
                                x-data="prospectingMap(@js($this->getActiveSession()), @js($this->getResults()))"
                                x-init="initMap()"
                            ></div>
                        </x-filament::section>

                        {{-- Results List --}}
                        <div class="space-y-3 max-h-[600px] overflow-y-auto">
                            @foreach($results as $result)
                                <div
                                    class="p-4 rounded-lg border transition-colors cursor-pointer
                                        {{ match($result->status) {
                                            'selected' => 'border-green-500 bg-green-50 dark:bg-green-900/20',
                                            'duplicate' => 'border-gray-300 bg-gray-50 dark:bg-gray-800 opacity-60',
                                            'imported' => 'border-blue-300 bg-blue-50 dark:bg-blue-900/20 opacity-60',
                                            default => 'border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600',
                                        } }}"
                                    wire:click="toggleResult({{ $result->id }})"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $result->title }}
                                            </h4>

                                            @if($result->review_rating || $result->review_count)
                                                <div class="flex items-center gap-2 mt-1">
                                                    @if($result->review_rating)
                                                        <span class="text-sm font-medium {{ $result->review_rating >= 4.0 ? 'text-green-600' : ($result->review_rating >= 3.0 ? 'text-yellow-600' : 'text-red-600') }}">
                                                            ★ {{ number_format($result->review_rating, 1) }}
                                                        </span>
                                                    @endif
                                                    @if($result->review_count)
                                                        <span class="text-xs text-gray-500">({{ $result->review_count }} {{ __('prospecting.reviews') }})</span>
                                                    @endif
                                                </div>
                                            @endif

                                            @if($result->address)
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $result->address }}
                                                </p>
                                            @endif

                                            @if($result->website)
                                                <p class="text-sm text-blue-600 dark:text-blue-400 truncate mt-1">
                                                    {{ $result->website }}
                                                </p>
                                            @endif

                                            {{-- Signals --}}
                                            @if($result->signals)
                                                <div class="flex flex-wrap gap-1 mt-2">
                                                    @foreach($result->signalDescriptions() as $signal)
                                                        <x-filament::badge size="sm" :color="match(true) {
                                                            str_contains($signal, 'Already') => 'gray',
                                                            str_contains($signal, 'No website') => 'warning',
                                                            str_contains($signal, 'No HTTPS') => 'warning',
                                                            str_contains($signal, 'Low') => 'info',
                                                            str_contains($signal, 'New') => 'success',
                                                            default => 'gray',
                                                        }">
                                                            {{ $signal }}
                                                        </x-filament::badge>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2 ml-3">
                                            @if($result->status === 'selected')
                                                <x-heroicon-s-check-circle class="w-6 h-6 text-green-500" />
                                            @elseif($result->status === 'imported')
                                                <x-heroicon-s-arrow-down-tray class="w-6 h-6 text-blue-500" />
                                            @elseif($result->status === 'duplicate')
                                                <x-heroicon-s-document-duplicate class="w-6 h-6 text-gray-400" />
                                            @endif

                                            @if(!$result->isImported() && $result->status !== 'duplicate')
                                                <button
                                                    wire:click.stop="dismissResult({{ $result->id }})"
                                                    class="text-gray-400 hover:text-red-500 transition-colors"
                                                    title="{{ __('prospecting.dismiss') }}"
                                                >
                                                    <x-heroicon-o-x-mark class="w-5 h-5" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Bulk Actions Bar --}}
                    <x-filament::section>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <x-filament::button size="sm" color="gray" wire:click="selectAll">
                                    {{ __('prospecting.select_all') }}
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="selectAllNoWebsite">
                                    {{ __('prospecting.select_no_website') }}
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="selectAllLowRating">
                                    {{ __('prospecting.select_low_rating') }}
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="selectAllLowReviews">
                                    {{ __('prospecting.select_low_reviews') }}
                                </x-filament::button>
                                <x-filament::button size="sm" color="warning" wire:click="clearSelection">
                                    {{ __('prospecting.clear_selection') }}
                                </x-filament::button>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                    {{ count($this->selectedResultIds) }} {{ __('prospecting.selected') }}
                                </span>
                                <x-filament::button
                                    color="success"
                                    icon="heroicon-o-arrow-down-tray"
                                    wire:click="openImportModal"
                                    :disabled="empty($this->selectedResultIds)"
                                >
                                    {{ __('prospecting.import_selected') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::section>
                @elseif($session->isCompleted() && $results->isEmpty())
                    <x-filament::section>
                        <div class="text-center py-8">
                            <x-heroicon-o-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ __('prospecting.no_results') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('prospecting.no_results_description') }}</p>
                        </div>
                    </x-filament::section>
                @endif
            @endif
        @endif
    </div>

    {{-- Import Modal --}}
    <x-filament::modal
        id="import-modal"
        :heading="__('prospecting.import_leads')"
        width="md"
        :visible="$showImportModal"
    >
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('prospecting.import_confirm_body', ['count' => count($this->selectedResultIds)]) }}
            </p>

            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('prospecting.assign_to') }}</label>
                <select wire:model="assign_to" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm">
                    <option value="">{{ __('prospecting.unassigned') }}</option>
                    @foreach($this->getUserOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('prospecting.tags') }}</label>
                <select wire:model="tag_ids" multiple class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm">
                    @foreach($this->getTagOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="importSelected" color="success">
                {{ __('prospecting.import') }}
            </x-filament::button>
            <x-filament::button wire:click="$set('showImportModal', false)" color="gray">
                {{ __('prospecting.cancel') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- Leaflet Map Script --}}
    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function prospectingMap(session, results) {
            return {
                map: null,
                markers: [],
                session: session,
                results: results,

                initMap() {
                    if (!this.session || !this.session.territory) return;

                    const center = [this.session.territory.latitude, this.session.territory.longitude];
                    const radiusM = this.session.territory.radius_km * 1000;

                    this.map = L.map('prospecting-map').setView(center, 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);

                    // Territory circle
                    L.circle(center, {
                        radius: radiusM,
                        color: '#1e5a96',
                        fillColor: '#1e5a96',
                        fillOpacity: 0.05,
                        weight: 1,
                    }).addTo(this.map);

                    // Result pins
                    this.results.forEach(result => {
                        if (!result.latitude || !result.longitude) return;

                        const color = {
                            'selected': '#22c55e',
                            'new': '#3b82f6',
                            'duplicate': '#9ca3af',
                            'imported': '#6366f1',
                        }[result.status] || '#3b82f6';

                        const marker = L.circleMarker([result.latitude, result.longitude], {
                            radius: 8,
                            fillColor: color,
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.8,
                        }).addTo(this.map);

                        marker.bindPopup(`
                            <strong>${result.title}</strong><br>
                            ${result.address || ''}<br>
                            ${result.review_rating ? '★ ' + result.review_rating : ''}
                            ${result.review_count ? '(' + result.review_count + ' reviews)' : ''}
                        `);

                        marker.on('click', () => {
                            @this.call('toggleResult', result.id);
                        });

                        this.markers.push(marker);
                    });

                    // Fit bounds to show all markers
                    if (this.markers.length > 0) {
                        const group = L.featureGroup(this.markers);
                        this.map.fitBounds(group.getBounds().pad(0.1));
                    }
                }
            };
        }
    </script>
    @endpush
</x-filament-panels::page>
