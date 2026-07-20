<?php

namespace App\Filament\Resources\ProjectReports\Pages;

use App\Filament\Resources\ProjectReports\ProjectReportResource;
use Filament\Resources\Pages\ListRecords;

class ListProjectReports extends ListRecords
{
    protected static string $resource = ProjectReportResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
