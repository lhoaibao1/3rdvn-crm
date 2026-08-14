<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Schemas\AclMixApplicationForm;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Filament\Resources\Applications\Schemas\LotteFinanceApplicationForm;
use App\Filament\Resources\FeDeeplinkApplications\Schemas\FeDeeplinkApplicationForm;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\ApplicationFinancialData;
use App\Support\Applications\LotteFinanceWorkflow;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getTitle(): string
    {
        return $this->record->applicant_name ?: ($this->record->application_code ?: 'Application');
    }

    public function getBreadcrumb(): string
    {
        return 'Sửa';
    }

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
        $isSaleStep = $this->record instanceof Application && match ($this->record->salesProject?->slug) {
            'acl-mix' => in_array($this->record->status, [AclMixWorkflow::SALE_COMPLETION, AclMixWorkflow::RETURNED_TO_SALE], true),
            'lotte-finance' => in_array($this->record->status, [LotteFinanceWorkflow::SALE_COMPLETION, LotteFinanceWorkflow::RETURNED_TO_SALE], true),
            default => false,
        };

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

        if ($this->record instanceof Application
            && $this->record->salesProject?->slug === 'lotte-finance'
            && in_array($this->record->status, [LotteFinanceWorkflow::SALE_COMPLETION, LotteFinanceWorkflow::RETURNED_TO_SALE], true)) {
            $this->record = LotteFinanceWorkflow::submitSaleInformation($this->record, auth()->user());
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

        return match ($this->record->salesProject?->slug) {
            'acl-mix' => AclMixApplicationForm::normalizeDataForSave($this->record, $data),
            'lotte-finance' => LotteFinanceApplicationForm::normalizeDataForSave($this->record, $data),
            'fe-deeplink' => FeDeeplinkApplicationForm::normalizeDataForSave($this->record, $data),
            default => ApplicationForm::normalizeDataForSave($this->record, $data),
        };
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->record instanceof Application) {
            return $data;
        }

        if (blank(data_get($data, 'payload.fields.disbursed_at'))) {
            $resolved = ApplicationFinancialData::disbursedAt($this->record);

            if ($resolved) {
                data_set($data, 'payload.fields.disbursed_at', $resolved->format('Y-m-d H:i:s'));
            }
        }

        return match ($this->record->salesProject?->slug) {
            'lotte-finance' => LotteFinanceApplicationForm::prepareDataForFill($data),
            default => $data,
        };
    }
}
