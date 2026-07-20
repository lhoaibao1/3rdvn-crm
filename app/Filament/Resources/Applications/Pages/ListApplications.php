<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;



    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
