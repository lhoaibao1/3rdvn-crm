<?php

namespace App\Filament\Resources\HotLeads\Pages;

use App\Filament\Resources\HotLeads\HotLeadResource;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use Filament\Resources\Pages\EditRecord;

class EditHotLead extends EditRecord
{
    protected static string $resource = HotLeadResource::class;

    public function getTitle(): string
    {
        return 'Sửa Lead nóng';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LeadForm::normalizeDataForSave($this->record, $data);
    }
}
