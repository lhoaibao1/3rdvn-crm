<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobVacancies extends ListRecords
{
    protected static string $resource = JobVacancyResource::class;

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo tin tuyển dụng'),
        ];
    }
}
