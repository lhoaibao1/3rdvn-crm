<?php

namespace App\Filament\Resources\CrmModules\Pages;

use App\Filament\Resources\CrmModules\CrmModuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCrmModule extends ViewRecord
{
    protected static string $resource = CrmModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedPencilSquare)];
    }
}
