<?php

namespace App\Filament\Resources\PlaybookResource\Pages;

use App\Filament\Resources\PlaybookResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlaybook extends CreateRecord
{
    protected static string $resource = PlaybookResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = filament()->getTenant()->id;

        return $data;
    }
}
