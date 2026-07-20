<?php

namespace App\Filament\Resources\ProcessingAssignmentConfigs\Pages;

use App\Filament\Resources\ProcessingAssignmentConfigs\ProcessingAssignmentConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListProcessingAssignmentConfigs extends ListRecords
{
    protected static string $resource = ProcessingAssignmentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo cấu hình')->icon(Heroicon::OutlinedPlus),
        ];
    }
}
