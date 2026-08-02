<?php

namespace App\Filament\Resources\DataCenterLeads\Pages;

use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDataCenterLead extends ViewRecord
{
    protected static string $resource = DataCenterLeadResource::class;

    public function getTitle(): string
    {
        return $this->record->customer_name ?: $this->record->referral_code;
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }
}
