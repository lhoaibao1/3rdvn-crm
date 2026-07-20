<?php

namespace App\Filament\Resources\CrmModules\Pages;

use App\Filament\Resources\CrmModules\CrmModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrmModules extends ListRecords
{
    protected static string $resource = CrmModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedPlus)];
    }
}
