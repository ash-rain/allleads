<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaybookResource\Pages;
use App\Models\Lead;
use App\Models\Playbook;
use App\Models\Tag;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlaybookResource extends Resource
{
    protected static ?string $model = Playbook::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-bookmark';
    }

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('common.nav_group_leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('playbooks.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('playbooks.resource_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('playbooks.resource_label_plural');
    }

    // ─── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Schemas\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('playbooks.field_name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('description')
                    ->label(__('playbooks.field_description'))
                    ->maxLength(500),

                Forms\Components\TextInput::make('icon')
                    ->label(__('playbooks.field_icon'))
                    ->placeholder('heroicon-o-star')
                    ->maxLength(100),

                Forms\Components\TextInput::make('sort_order')
                    ->label(__('playbooks.field_sort_order'))
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('playbooks.field_is_active'))
                    ->default(true)
                    ->columnSpanFull(),
            ]),

            Schemas\Components\Section::make(__('playbooks.section_filters'))->schema([
                Forms\Components\Toggle::make('filters.no_website')
                    ->label(__('playbooks.filter_no_website')),

                Forms\Components\Toggle::make('filters.has_email')
                    ->label(__('playbooks.filter_has_email')),

                Forms\Components\TextInput::make('filters.rating_min')
                    ->label(__('playbooks.filter_rating_min'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5)
                    ->step(0.1),

                Forms\Components\Select::make('filters.categories')
                    ->label(__('playbooks.filter_categories'))
                    ->multiple()
                    ->searchable()
                    ->options(
                        fn () => Lead::query()
                            ->select('category')
                            ->distinct()
                            ->whereNotNull('category')
                            ->orderBy('category')
                            ->pluck('category', 'category')
                            ->toArray()
                    ),

                Forms\Components\Select::make('filters.tags')
                    ->label(__('playbooks.filter_tags'))
                    ->multiple()
                    ->options(Tag::pluck('name', 'id')),
            ]),
        ]);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('playbooks.field_name'))
                    ->icon(fn (Playbook $record): string => $record->icon ?? 'heroicon-o-bookmark')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('playbooks.field_description'))
                    ->limit(60)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('playbooks.field_is_active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('playbooks.field_sort_order'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('filter_summary')
                    ->label(__('playbooks.column_filter_summary'))
                    ->getStateUsing(fn (Playbook $record): string => $record->filterSummary()),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('playbooks.field_is_active')),
            ])
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

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaybooks::route('/'),
            'create' => Pages\CreatePlaybook::route('/create'),
            'edit' => Pages\EditPlaybook::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('business_id', filament()->getTenant()->id);
    }
}
