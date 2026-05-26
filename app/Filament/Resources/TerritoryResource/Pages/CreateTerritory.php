<?php

namespace App\Filament\Resources\TerritoryResource\Pages;

use App\Filament\Resources\TerritoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTerritory extends CreateRecord
{
    protected static string $resource = TerritoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = filament()->getTenant()->id;
        $data['created_by'] = auth()->id();

        return $data;
    }
}
