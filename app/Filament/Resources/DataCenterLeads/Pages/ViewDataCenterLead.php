<?php

namespace App\Filament\Resources\DataCenterLeads\Pages;

use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDataCenterLead extends ViewRecord
{
    protected static string $resource = DataCenterLeadResource::class;

    public function getTitle(): string
    {
        return 'Lead Referral';
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }
}
