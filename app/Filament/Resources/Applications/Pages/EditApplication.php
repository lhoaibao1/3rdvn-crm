<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Schemas\ApplicationForm;
use App\Models\Application;
use Filament\Resources\Pages\EditRecord;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->record instanceof Application
            ? ApplicationForm::normalizeDataForSave($this->record, $data)
            : $data;
    }
}
