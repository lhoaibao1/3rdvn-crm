<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedEye),
            DeleteAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedTrash),
        ];
    }
}
