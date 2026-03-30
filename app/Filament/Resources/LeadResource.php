<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Leads';
    }

    public static function getNavigationLabel(): string
    {
        return __('leads.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('leads.resource_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leads.resource_label_plural');
    }

    // ─── Form ─────────────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('leads.field_title'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('category')
                    ->label(__('leads.field_category'))
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label(__('leads.field_phone'))
                    ->tel()
                    ->maxLength(50),

                Forms\Components\TextInput::make('email')
                    ->label(__('leads.field_email'))
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('website')
                    ->label(__('leads.field_website'))
                    ->url()
                    ->maxLength(255),

                Forms\Components\TextInput::make('address')
                    ->label(__('leads.field_address'))
                    ->maxLength(500),

                Forms\Components\TextInput::make('review_rating')
                    ->label(__('leads.field_review_rating'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5)
                    ->step(0.1),

                Forms\Components\Select::make('status')
                    ->label(__('leads.field_status'))
                    ->options([
                        Lead::STATUS_NEW => __('leads.status_new'),
                        Lead::STATUS_CONTACTED => __('leads.status_contacted'),
                        Lead::STATUS_REPLIED => __('leads.status_replied'),
                        Lead::STATUS_CLOSED => __('leads.status_closed'),
                        Lead::STATUS_DISQUALIFIED => __('leads.status_disqualified'),
                    ])
                    ->default(Lead::STATUS_NEW)
                    ->required(),

                Forms\Components\Select::make('assigned_to')
                    ->label(__('leads.field_assignee'))
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),
            ]),

            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('tags')
                    ->label(__('leads.field_tags'))
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->preload(),
            ]),
        ]);
    }

    // ─── Table ────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('leads.field_title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('leads.field_category'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('review_rating')
                    ->label(__('leads.field_review_rating'))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        $state >= 2.5 => 'gray',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('website')
                    ->label(__('leads.field_website'))
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn (Lead $record): bool => ! empty($record->website)),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('leads.field_email'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('leads.field_status'))
                    ->formatStateUsing(fn (string $state) => __("leads.status_{$state}"))
                    ->color(fn (string $state) => match ($state) {
                        Lead::STATUS_NEW => 'primary',
                        Lead::STATUS_CONTACTED => 'info',
                        Lead::STATUS_REPLIED => 'warning',
                        Lead::STATUS_CLOSED => 'success',
                        Lead::STATUS_DISQUALIFIED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignee.name')
                    ->label(__('leads.field_assignee'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tags.name')
                    ->label(__('leads.field_tags'))
                    ->badge()
                    ->color(fn ($state, Lead $record) => $record->tags
                        ->firstWhere('name', $state)?->color ?? 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('leads.field_created_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('leads.filter_status'))
                    ->options([
                        Lead::STATUS_NEW => __('leads.status_new'),
                        Lead::STATUS_CONTACTED => __('leads.status_contacted'),
                        Lead::STATUS_REPLIED => __('leads.status_replied'),
                        Lead::STATUS_CLOSED => __('leads.status_closed'),
                        Lead::STATUS_DISQUALIFIED => __('leads.status_disqualified'),
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('web_dev_prospects')
                    ->label(__('leads.preset_web_dev_prospects'))
                    ->query(fn (Builder $query) => $query->webDevProspects())
                    ->toggle(),

                Tables\Filters\Filter::make('no_website')
                    ->label(__('leads.filter_no_website'))
                    ->query(fn (Builder $query) => $query->noWebsite())
                    ->toggle(),

                Tables\Filters\Filter::make('has_email')
                    ->label(__('leads.filter_has_email'))
                    ->query(fn (Builder $query) => $query->whereNotNull('email'))
                    ->toggle(),

                Tables\Filters\Filter::make('rating_min')
                    ->label(__('leads.filter_rating_min'))
                    ->form([
                        Forms\Components\TextInput::make('rating_from')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->placeholder('0'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['rating_from'] !== null && $data['rating_from'] !== '',
                            fn ($q) => $q->where('review_rating', '>=', $data['rating_from'])
                        );
                    }),

                Tables\Filters\SelectFilter::make('category')
                    ->label(__('leads.filter_category'))
                    ->options(
                        fn () => Lead::query()
                            ->select('category')
                            ->distinct()
                            ->whereNotNull('category')
                            ->orderBy('category')
                            ->pluck('category', 'category')
                            ->toArray()
                    )
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label(__('leads.filter_assignee'))
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tags')
                    ->label(__('leads.filter_tags'))
                    ->options(Tag::pluck('name', 'id'))
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $data['values']));
                    }),

                Tables\Filters\SelectFilter::make('import_batch_id')
                    ->label(__('leads.filter_import_batch'))
                    ->options(
                        ImportBatch::query()
                            ->orderByDesc('created_at')
                            ->pluck('filename', 'id')
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('created_at')
                    ->label(__('leads.filter_date_from'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('leads.filter_date_from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('leads.filter_date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    // Assign
                    Actions\BulkAction::make('assign')
                        ->label(__('leads.action_assign'))
                        ->icon('heroicon-o-user')
                        ->form([
                            Forms\Components\Select::make('user_id')
                                ->label(__('leads.field_assignee'))
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['assigned_to' => $data['user_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Change status
                    Actions\BulkAction::make('change_status')
                        ->label(__('leads.action_change_status'))
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('leads.field_status'))
                                ->options([
                                    Lead::STATUS_NEW => __('leads.status_new'),
                                    Lead::STATUS_CONTACTED => __('leads.status_contacted'),
                                    Lead::STATUS_REPLIED => __('leads.status_replied'),
                                    Lead::STATUS_CLOSED => __('leads.status_closed'),
                                    Lead::STATUS_DISQUALIFIED => __('leads.status_disqualified'),
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['status' => $data['status']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Add tag
                    Actions\BulkAction::make('add_tag')
                        ->label(__('leads.action_add_tag'))
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('tag_id')
                                ->label(__('leads.field_tags'))
                                ->options(Tag::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->tags()->syncWithoutDetaching([$data['tag_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Remove tag
                    Actions\BulkAction::make('remove_tag')
                        ->label(__('leads.action_remove_tag'))
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('tag_id')
                                ->label(__('leads.field_tags'))
                                ->options(Tag::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->tags()->detach($data['tag_id']);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─── Pages ────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tags', 'assignee']);
    }
}
