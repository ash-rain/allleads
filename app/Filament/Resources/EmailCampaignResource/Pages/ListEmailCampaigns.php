<?php

namespace App\Filament\Resources\EmailCampaignResource\Pages;

use App\Filament\Resources\EmailCampaignResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListEmailCampaigns extends ListRecords
{
    protected static string $resource = EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
