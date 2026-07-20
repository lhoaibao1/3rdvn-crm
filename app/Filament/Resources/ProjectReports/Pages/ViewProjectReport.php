<?php

namespace App\Filament\Resources\ProjectReports\Pages;

use App\Filament\Resources\ProjectReports\ProjectReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectReport extends ViewRecord
{
    protected static string $resource = ProjectReportResource::class;

    public function getTitle(): string
    {
        return 'Báo cáo';
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }
}
