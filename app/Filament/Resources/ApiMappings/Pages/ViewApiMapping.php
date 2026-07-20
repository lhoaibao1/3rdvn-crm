<?php

namespace App\Filament\Resources\ApiMappings\Pages;

use App\Filament\Resources\ApiMappings\ApiMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApiMapping extends ViewRecord
{
    protected static string $resource = ApiMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedPencilSquare),
        ];
    }
}
