<?php

namespace App\Filament\Resources\ApiMappings\Pages;

use App\Filament\Resources\ApiMappings\ApiMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApiMappings extends ListRecords
{
    protected static string $resource = ApiMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedPlus),
        ];
    }
}
