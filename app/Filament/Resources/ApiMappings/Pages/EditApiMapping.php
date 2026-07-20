<?php

namespace App\Filament\Resources\ApiMappings\Pages;

use App\Filament\Resources\ApiMappings\ApiMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditApiMapping extends EditRecord
{
    protected static string $resource = ApiMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedEye),
            DeleteAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedTrash),
            ForceDeleteAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedArchiveBoxXMark)->icon(\Filament\Support\Icons\Heroicon::OutlinedTrash),
            RestoreAction::make()->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowUturnLeft),
        ];
    }
}
