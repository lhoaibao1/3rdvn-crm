<?php

namespace App\Filament\Resources\DataCenterLeads\Pages;

use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListDataCenterLeads extends ListRecords
{
    protected static string $resource = DataCenterLeadResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
