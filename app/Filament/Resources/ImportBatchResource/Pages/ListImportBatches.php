<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use App\Jobs\ImportLeadsJob;
use App\Models\ImportBatch;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListImportBatches extends ListRecords
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label(__('leads.action_import'))
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    Forms\Components\FileUpload::make('file')
                        ->label(__('leads.import_file'))
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('imports')
                        ->required(),

                    Forms\Components\Select::make('assign_to')
                        ->label(__('leads.import_assign_to'))
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Select::make('tags')
                        ->label(__('leads.field_tags'))
                        ->options(fn () => Tag::orderBy('name')->pluck('name', 'id'))
                        ->multiple()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    $absolutePath = Storage::disk('local')->path($data['file']);

                    $batch = ImportBatch::create([
                        'uuid' => Str::uuid(),
                        'filename' => basename($data['file']),
                        'status' => 'pending',
                        'progress' => 0,
                        'total' => 0,
                        'created_count' => 0,
                        'updated_count' => 0,
                        'skipped_count' => 0,
                        'failed_count' => 0,
                        'created_by' => Auth::id(),
                    ]);

                    ImportLeadsJob::dispatch(
                        $batch->id,
                        $absolutePath,
                        'text/csv',
                        $data['assign_to'] ?? null,
                        $data['tags'] ?? [],
                        Auth::id(),
                    );

                    Notification::make()
                        ->title(__('leads.import_started'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
