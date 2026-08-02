<?php

namespace App\Filament\Resources\LotteFinanceApplications\Pages;

use App\Filament\Resources\LotteFinanceApplications\LotteFinanceApplicationResource;
use App\Models\Application;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Filament\LeadCreate\CreateLotteFinanceLeadAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLotteFinanceApplication extends CreateRecord
{
    protected static string $resource = LotteFinanceApplicationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tạo hồ sơ Lotte Finance';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return LotteFinanceWorkflow::create(
            $data,
            auth()->user(),
            CreateLotteFinanceLeadAction::fieldKeys(),
        );
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        /** @var Application $record */
        $record = $this->record;

        return static::getResource()::getUrl('view', ['record' => $record]);
    }
}
