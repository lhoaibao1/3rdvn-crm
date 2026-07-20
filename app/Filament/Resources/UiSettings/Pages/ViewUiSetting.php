<?php

namespace App\Filament\Resources\UiSettings\Pages;

use App\Filament\Resources\UiSettings\UiSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUiSetting extends ViewRecord
{
    protected static string $resource = UiSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedPencilSquare),
        ];
    }
}
