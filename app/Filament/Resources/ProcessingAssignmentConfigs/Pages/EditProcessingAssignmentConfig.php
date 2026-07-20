<?php

namespace App\Filament\Resources\ProcessingAssignmentConfigs\Pages;

use App\Filament\Resources\ProcessingAssignmentConfigs\ProcessingAssignmentConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProcessingAssignmentConfig extends EditRecord
{
    protected static string $resource = ProcessingAssignmentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Xóa cấu hình')];
    }
}
