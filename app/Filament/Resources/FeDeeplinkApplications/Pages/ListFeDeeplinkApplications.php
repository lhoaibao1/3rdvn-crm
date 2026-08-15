<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Pages;

use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\FeDeeplinkApplications\FeDeeplinkApplicationResource;

class ListFeDeeplinkApplications extends ListApplications
{
    protected static string $resource = FeDeeplinkApplicationResource::class;

    public function getHeading(): string
    {
        return 'Danh sách khách hàng FE Deeplink';
    }

    public function getSubheading(): ?string
    {
        return 'FE - Cash Loan - Deeplink';
    }
}
