<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tạo hồ sơ ACL Mix';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return AclMixWorkflow::create($data, auth()->user());
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Tạo hồ sơ')
            ->icon(Heroicon::OutlinedPaperAirplane);
    }

    protected function getRedirectUrl(): string
    {
        /** @var Application $record */
        $record = $this->record;

        return static::getResource()::getUrl('view', ['record' => $record]);
    }
}
