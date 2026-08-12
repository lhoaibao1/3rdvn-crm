<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditWorkflowConfiguration extends EditRecord
{
    protected static string $resource = WorkflowConfigurationResource::class;

    public function getTitle(): string
    {
        return 'Cấu hình Workflow · '.$this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Xem sơ đồ')
                ->icon(Heroicon::OutlinedEye),
            DeleteAction::make()->label('Xóa Workflow'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Lưu Workflow')
            ->icon(Heroicon::OutlinedCheck);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
