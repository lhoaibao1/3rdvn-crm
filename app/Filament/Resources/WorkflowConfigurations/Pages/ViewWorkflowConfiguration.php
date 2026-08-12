<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewWorkflowConfiguration extends ViewRecord
{
    protected static string $resource = WorkflowConfigurationResource::class;

    public function getTitle(): string
    {
        return 'Workflow · '.$this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Cấu hình chuyển bước')
                ->icon(Heroicon::OutlinedPencilSquare),
            DeleteAction::make()->label('Xóa Workflow'),
        ];
    }
}
