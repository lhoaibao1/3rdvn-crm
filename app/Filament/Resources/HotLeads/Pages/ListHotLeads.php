<?php

namespace App\Filament\Resources\HotLeads\Pages;

use App\Filament\Resources\HotLeads\HotLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListHotLeads extends ListRecords
{
    protected static string $resource = HotLeadResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
