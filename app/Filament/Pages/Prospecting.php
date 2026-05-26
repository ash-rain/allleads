<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TerritoryResource;
use App\Jobs\ImportProspectingResultsJob;
use App\Jobs\RunProspectingSearchJob;
use App\Models\ProspectingResult;
use App\Models\ProspectingSession;
use App\Models\Tag;
use App\Models\Territory;
use App\Models\User;
use App\Traits\HasPlaybooks;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class Prospecting extends Page implements HasForms
{
    use HasPlaybooks, InteractsWithForms;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.prospecting';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-magnifying-glass-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('common.nav_group_leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('prospecting.discover_leads');
    }

    protected static ?string $title = null;

    // ─── Search Form State ───────────────────────────────────────────────────
    public ?int $territory_id = null;

    public string $search_query = '';

    public ?float $min_rating = null;

    public ?float $max_rating = null;

    public ?int $min_reviews = null;

    public ?int $max_reviews = null;

    public ?bool $has_website = null;

    public ?bool $has_phone = null;

    // ─── Session State ───────────────────────────────────────────────────────
    public ?int $activeSessionId = null;

    public array $selectedResultIds = [];

    // ─── Import Form State ───────────────────────────────────────────────────
    public ?int $assign_to = null;

    public array $tag_ids = [];

    public bool $showImportModal = false;

    public function getTitle(): string
    {
        return static::$title ?? __('prospecting.discover_leads');
    }

    protected function getForms(): array
    {
        return ['searchForm'];
    }

    // ─── Search Form ─────────────────────────────────────────────────────────

    public function searchForm(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('territory_id')
                ->label(__('prospecting.territory'))
                ->options(fn () => Territory::where('business_id', filament()->getTenant()->id)
                    ->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                        ->label(__('prospecting.territory_name'))
                        ->required(),
                    Forms\Components\TextInput::make('radius_km')
                        ->label(__('prospecting.radius_km'))
                        ->numeric()
                        ->default(10)
                        ->suffix('km')
                        ->required(),
                    TerritoryResource::locationSearchField(),
                    Forms\Components\TextInput::make('latitude')
                        ->label(__('prospecting.latitude'))
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('longitude')
                        ->label(__('prospecting.longitude'))
                        ->numeric()
                        ->required(),
                ])
                ->createOptionUsing(function (array $data): int {
                    $territory = Territory::create([
                        'business_id' => filament()->getTenant()->id,
                        'created_by' => auth()->id(),
                        ...$data,
                    ]);

                    return $territory->id;
                }),

            Forms\Components\TextInput::make('search_query')
                ->label(__('prospecting.search_query'))
                ->placeholder('e.g. dentist, plumber, restaurant')
                ->required(),

            Fieldset::make(__('prospecting.filters'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('min_rating')
                        ->label(__('prospecting.min_rating'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(5)
                        ->step(0.1),

                    Forms\Components\TextInput::make('max_rating')
                        ->label(__('prospecting.max_rating'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(5)
                        ->step(0.1),

                    Forms\Components\TextInput::make('min_reviews')
                        ->label(__('prospecting.min_reviews'))
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\Select::make('has_website')
                        ->label(__('prospecting.has_website'))
                        ->options([
                            '' => __('prospecting.any'),
                            '1' => __('prospecting.yes'),
                            '0' => __('prospecting.no'),
                        ]),

                    Forms\Components\Select::make('has_phone')
                        ->label(__('prospecting.has_phone'))
                        ->options([
                            '' => __('prospecting.any'),
                            '1' => __('prospecting.yes'),
                            '0' => __('prospecting.no'),
                        ]),
                ]),
        ]);
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function startSearch(): void
    {
        $this->validate([
            'territory_id' => 'required|exists:territories,id',
            'search_query' => 'required|string|max:255',
        ]);

        $territory = Territory::findOrFail($this->territory_id);

        $filters = array_filter([
            'min_rating' => $this->min_rating,
            'max_rating' => $this->max_rating,
            'min_reviews' => $this->min_reviews,
            'max_reviews' => $this->max_reviews,
            'has_website' => $this->has_website !== null && $this->has_website !== ''
                ? (bool) $this->has_website
                : null,
            'has_phone' => $this->has_phone !== null && $this->has_phone !== ''
                ? (bool) $this->has_phone
                : null,
        ], fn ($value) => $value !== null);

        $session = ProspectingSession::create([
            'business_id' => filament()->getTenant()->id,
            'territory_id' => $territory->id,
            'search_query' => $this->search_query,
            'filters' => ! empty($filters) ? $filters : null,
            'status' => ProspectingSession::STATUS_PENDING,
            'created_by' => auth()->id(),
        ]);

        $this->activeSessionId = $session->id;

        RunProspectingSearchJob::dispatch($session->id);

        Notification::make()
            ->title(__('prospecting.search_started'))
            ->body(__('prospecting.search_started_body', ['query' => $this->search_query, 'territory' => $territory->name]))
            ->info()
            ->send();
    }

    public function loadSession(int $sessionId): void
    {
        $this->activeSessionId = $sessionId;
        $this->selectedResultIds = [];
    }

    // ─── Result Selection ────────────────────────────────────────────────────

    public function toggleResult(int $resultId): void
    {
        $result = ProspectingResult::find($resultId);

        if (! $result || $result->isImported()) {
            return;
        }

        if (in_array($resultId, $this->selectedResultIds)) {
            $this->selectedResultIds = array_values(array_diff($this->selectedResultIds, [$resultId]));
            $result->update(['status' => ProspectingResult::STATUS_NEW]);
        } else {
            $this->selectedResultIds[] = $resultId;
            $result->update(['status' => ProspectingResult::STATUS_SELECTED]);
        }
    }

    public function dismissResult(int $resultId): void
    {
        $result = ProspectingResult::find($resultId);

        if (! $result || $result->isImported()) {
            return;
        }

        $result->update(['status' => ProspectingResult::STATUS_DISMISSED]);
        $this->selectedResultIds = array_values(array_diff($this->selectedResultIds, [$resultId]));

        $this->getActiveSession()?->refreshCounts();
    }

    public function selectAllNoWebsite(): void
    {
        $this->bulkSelect(fn ($query) => $query->whereNull('website'));
    }

    public function selectAllLowRating(): void
    {
        $this->bulkSelect(fn ($query) => $query->where('review_rating', '<', 3.5));
    }

    public function selectAllLowReviews(): void
    {
        $this->bulkSelect(fn ($query) => $query->where('review_count', '<', 20));
    }

    public function selectAll(): void
    {
        $this->bulkSelect();
    }

    public function clearSelection(): void
    {
        if (! $this->activeSessionId) {
            return;
        }

        ProspectingResult::where('prospecting_session_id', $this->activeSessionId)
            ->where('status', ProspectingResult::STATUS_SELECTED)
            ->update(['status' => ProspectingResult::STATUS_NEW]);

        $this->selectedResultIds = [];
    }

    private function bulkSelect(?\Closure $additionalQuery = null): void
    {
        if (! $this->activeSessionId) {
            return;
        }

        $query = ProspectingResult::where('prospecting_session_id', $this->activeSessionId)
            ->where('status', ProspectingResult::STATUS_NEW);

        if ($additionalQuery) {
            $additionalQuery($query);
        }

        $ids = $query->pluck('id')->all();
        ProspectingResult::whereIn('id', $ids)->update(['status' => ProspectingResult::STATUS_SELECTED]);

        $this->selectedResultIds = array_unique(array_merge($this->selectedResultIds, $ids));
    }

    // ─── Import ──────────────────────────────────────────────────────────────

    public function openImportModal(): void
    {
        if (empty($this->selectedResultIds)) {
            Notification::make()
                ->title(__('prospecting.no_results_selected'))
                ->warning()
                ->send();

            return;
        }

        $this->showImportModal = true;
    }

    public function importSelected(): void
    {
        if (empty($this->selectedResultIds) || ! $this->activeSessionId) {
            return;
        }

        ImportProspectingResultsJob::dispatch(
            sessionId: $this->activeSessionId,
            resultIds: $this->selectedResultIds,
            assignTo: $this->assign_to,
            tagIds: $this->tag_ids,
            triggeredBy: auth()->id(),
        );

        $count = count($this->selectedResultIds);

        Notification::make()
            ->title(__('prospecting.import_started'))
            ->body(__('prospecting.import_started_body', ['count' => $count]))
            ->success()
            ->send();

        $this->showImportModal = false;
        $this->selectedResultIds = [];
        $this->assign_to = null;
        $this->tag_ids = [];
    }

    // ─── Computed Properties ─────────────────────────────────────────────────

    public function getActiveSession(): ?ProspectingSession
    {
        if (! $this->activeSessionId) {
            return null;
        }

        return ProspectingSession::with('territory')->find($this->activeSessionId);
    }

    public function getResults(): Collection
    {
        if (! $this->activeSessionId) {
            return collect();
        }

        return ProspectingResult::where('prospecting_session_id', $this->activeSessionId)
            ->whereNot('status', ProspectingResult::STATUS_DISMISSED)
            ->orderByRaw("CASE
                WHEN status = 'selected' THEN 0
                WHEN status = 'new' THEN 1
                WHEN status = 'duplicate' THEN 2
                WHEN status = 'imported' THEN 3
                ELSE 4
            END")
            ->orderByDesc('review_rating')
            ->get();
    }

    public function getRecentSessions(): Collection
    {
        return ProspectingSession::with('territory')
            ->where('business_id', filament()->getTenant()->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function getUserOptions(): array
    {
        return User::whereHas('businesses', fn ($q) => $q->where('businesses.id', filament()->getTenant()->id))
            ->pluck('name', 'id')
            ->all();
    }

    public function getTagOptions(): array
    {
        return Tag::where('business_id', filament()->getTenant()->id)
            ->pluck('name', 'id')
            ->all();
    }
}
