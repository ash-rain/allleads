<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TerritoryResource\Pages;
use App\Models\Territory;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TerritoryResource extends Resource
{
    protected static ?string $model = Territory::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-map-pin';
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('common.nav_group_leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('prospecting.territories');
    }

    public static function getModelLabel(): string
    {
        return __('prospecting.territory');
    }

    public static function getPluralModelLabel(): string
    {
        return __('prospecting.territories');
    }

    // ─── Form ─────────────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make(__('prospecting.territory_details'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('prospecting.territory_name'))
                        ->placeholder('e.g. Central Manchester')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('radius_km')
                        ->label(__('prospecting.radius_km'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(50)
                        ->step(0.5)
                        ->default(10)
                        ->suffix('km')
                        ->required(),

                    static::locationSearchField(),

                    Forms\Components\TextInput::make('latitude')
                        ->label(__('prospecting.latitude'))
                        ->numeric()
                        ->required()
                        ->step(0.0000001),

                    Forms\Components\TextInput::make('longitude')
                        ->label(__('prospecting.longitude'))
                        ->numeric()
                        ->required()
                        ->step(0.0000001),
                ]),
        ]);
    }

    public static function locationSearchField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('location_search')
            ->label(__('prospecting.location_search'))
            ->placeholder(__('prospecting.location_search_placeholder'))
            ->helperText(__('prospecting.location_search_helper'))
            ->searchable()
            ->getOptionLabelUsing(function (?string $state): ?string {
                if (! $state) {
                    return null;
                }
                $data = json_decode($state, true);

                return $data['name'] ?? null;
            })
            ->getSearchResultsUsing(function (string $search): array {
                $results = Http::timeout(5)
                    ->withHeaders(['User-Agent' => config('app.name').'/1.0 territory-geocoder'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $search,
                        'format' => 'json',
                        'limit' => 5,
                        'addressdetails' => 0,
                    ])
                    ->json();

                return collect($results ?? [])
                    ->mapWithKeys(fn ($r) => [
                        json_encode(['lat' => $r['lat'], 'lon' => $r['lon'], 'name' => $r['display_name']]) => $r['display_name'],
                    ])
                    ->all();
            })
            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                if (! $state) {
                    return;
                }

                $data = json_decode($state, true);

                if (! $data) {
                    return;
                }

                $set('latitude', $data['lat']);
                $set('longitude', $data['lon']);

                if (! filled($get('name'))) {
                    $set('name', Str::limit($data['name'], 100));
                }
            })
            ->live()
            ->dehydrated(false)
            ->columnSpanFull();
    }

    // ─── Table ────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('prospecting.territory_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('radius_km')
                    ->label(__('prospecting.radius_km'))
                    ->suffix(' km')
                    ->sortable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label(__('prospecting.latitude'))
                    ->numeric(7)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('longitude')
                    ->label(__('prospecting.longitude'))
                    ->numeric(7)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('prospecting_sessions_count')
                    ->label(__('prospecting.sessions_count'))
                    ->counts('prospectingSessions')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('common.created_by'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTerritories::route('/'),
            'create' => Pages\CreateTerritory::route('/create'),
            'edit' => Pages\EditTerritory::route('/{record}/edit'),
        ];
    }
}
