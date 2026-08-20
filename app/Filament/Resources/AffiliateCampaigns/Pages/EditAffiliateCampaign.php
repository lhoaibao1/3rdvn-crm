<?php

namespace App\Filament\Resources\AffiliateCampaigns\Pages;

use App\Filament\Resources\AffiliateCampaigns\AffiliateCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateCampaign extends EditRecord
{
    protected static string $resource = AffiliateCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
