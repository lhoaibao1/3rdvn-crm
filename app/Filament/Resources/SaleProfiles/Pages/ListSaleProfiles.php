<?php

namespace App\Filament\Resources\SaleProfiles\Pages;

use App\Filament\Resources\SaleProfiles\SaleProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListSaleProfiles extends ListRecords
{
    protected static string $resource = SaleProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
