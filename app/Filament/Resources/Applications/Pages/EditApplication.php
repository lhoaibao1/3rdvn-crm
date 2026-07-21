<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Schemas\AclMixApplicationForm;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Xóa')
                ->icon(Heroicon::OutlinedTrash)
                ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        $isSaleStep = $this->record instanceof Application
            && $this->record->salesProject?->slug === 'acl-mix'
            && in_array($this->record->status, [
                AclMixWorkflow::SALE_COMPLETION,
                AclMixWorkflow::RETURNED_TO_SALE,
            ], true);

        return parent::getSaveFormAction()
            ->label($isSaleStep ? 'Cập nhật và chuyển bước' : 'Lưu thay đổi')
            ->icon($isSaleStep ? Heroicon::OutlinedArrowRightCircle : Heroicon::OutlinedCheck);
    }

    protected function afterSave(): void
    {
        if ($this->record instanceof Application
            && $this->record->salesProject?->slug === 'acl-mix'
            && in_array($this->record->status, [
                AclMixWorkflow::SALE_COMPLETION,
                AclMixWorkflow::RETURNED_TO_SALE,
            ], true)) {
            $this->record = AclMixWorkflow::submitSaleInformation($this->record, auth()->user());
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->record instanceof Application) {
            return $data;
        }

        return $this->record->salesProject?->slug === 'acl-mix'
            ? AclMixApplicationForm::normalizeDataForSave($this->record, $data)
            : ApplicationForm::normalizeDataForSave($this->record, $data);
    }
}
