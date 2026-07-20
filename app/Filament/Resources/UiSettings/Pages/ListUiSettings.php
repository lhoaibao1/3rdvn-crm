<?php

namespace App\Filament\Resources\UiSettings\Pages;

use App\Filament\Resources\UiSettings\UiSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUiSettings extends ListRecords
{
    protected static string $resource = UiSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedPlus),
        ];
    }
}
