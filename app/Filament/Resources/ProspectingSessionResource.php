<?php

namespace App\Filament\Resources;

use App\Filament\Pages\Prospecting;
use App\Filament\Resources\ProspectingSessionResource\Pages;
use App\Models\ProspectingSession;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProspectingSessionResource extends Resource
{
    protected static ?string $model = ProspectingSession::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clock';
    }

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('common.nav_group_leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('prospecting.session_history');
    }

    public static function getModelLabel(): string
    {
        return __('prospecting.session');
    }

    public static function getPluralModelLabel(): string
    {
        return __('prospecting.sessions');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('search_query')
                    ->label(__('prospecting.search_query'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('territory.name')
                    ->label(__('prospecting.territory'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('common.status'))
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'searching' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('result_count')
                    ->label(__('prospecting.results'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('imported_count')
                    ->label(__('prospecting.imported'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('dismissed_count')
                    ->label(__('prospecting.dismissed'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sources_used')
                    ->label(__('prospecting.sources'))
                    ->getStateUsing(fn (ProspectingSession $record): string => implode(', ', $record->sources_used ?? []))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('common.created_by'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('searched_at')
                    ->label(__('prospecting.searched_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('reopen')
                    ->label(__('prospecting.reopen'))
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn (ProspectingSession $record): string => Prospecting::getUrl().'?session='.$record->id)
                    ->visible(fn (ProspectingSession $record) => $record->isCompleted()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspectingSessions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('territory');
    }
}
