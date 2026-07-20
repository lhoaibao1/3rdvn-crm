<?php

namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobVacancy extends EditRecord
{
    protected static string $resource = JobVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Xóa tin')->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return JobVacancyResource::getUrl('index');
    }
}
