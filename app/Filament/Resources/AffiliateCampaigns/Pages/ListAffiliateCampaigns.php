<?php

namespace App\Filament\Resources\AffiliateCampaigns\Pages;

use App\Filament\Resources\AffiliateCampaigns\AffiliateCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateCampaigns extends ListRecords
{
    protected static string $resource = AffiliateCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Thêm chiến dịch mới'),
        ];
    }
}
