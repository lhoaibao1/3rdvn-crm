<?php

namespace App\Filament\Resources\CrmModules\Pages;

use App\Filament\Resources\CrmModules\CrmModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCrmModule extends EditRecord
{
    protected static string $resource = CrmModuleResource::class;

    protected function afterSave(): void
    {
        $this->js('setTimeout(() => window.location.reload(), 250)');
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedEye), DeleteAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedTrash)];
    }
}
