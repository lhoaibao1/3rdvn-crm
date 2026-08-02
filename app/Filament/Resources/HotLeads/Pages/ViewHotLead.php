<?php

namespace App\Filament\Resources\HotLeads\Pages;

use App\Filament\Resources\HotLeads\HotLeadResource;
use Filament\Resources\Pages\ViewRecord;

class ViewHotLead extends ViewRecord
{
    protected static string $resource = HotLeadResource::class;

    public function getTitle(): string
    {
        return $this->record->lead_name ?: $this->record->lead_code;
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }
}
