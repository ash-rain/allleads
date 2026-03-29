<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportBatchResource\Pages;
use App\Models\ImportBatch;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImportBatchResource extends Resource
{
    protected static ?string $model           = ImportBatch::class;
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Leads';

    public static function getModelLabel(): string
    {
        return __('common.import_batch');
    }
    public static function getPluralModelLabel(): string
    {
        return __('common.import_batches');
    }
    public static function getNavigationLabel(): string
    {
        return __('common.import_batches');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label(__('common.filename'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('common.status'))
                    ->color(fn(string $state) => match ($state) {
                        'pending'    => 'gray',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'failed'     => 'danger',
                        'undone'     => 'warning',
                        default      => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('common.total'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_count')
                    ->label(__('common.created'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_count')
                    ->label(__('common.updated'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('skipped_count')
                    ->label(__('common.skipped'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label(__('common.failed'))
                    ->numeric()
                    ->color(fn($state) => $state > 0 ? 'danger' : null)
                    ->alignCenter(),

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
                Tables\Actions\Action::make('undo')
                    ->label(__('common.undo'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(ImportBatch $record) => ! $record->isUndone() && $record->status === 'completed')
                    ->action(function (ImportBatch $record): void {
                        $record->leads()->delete();
                        $record->update(['status' => 'undone', 'undone_at' => now()]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
        ];
    }
}
