<?php

namespace App\Filament\Resources\PlaybookResource\Pages;

use App\Filament\Resources\PlaybookResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlaybook extends EditRecord
{
    protected static string $resource = PlaybookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
