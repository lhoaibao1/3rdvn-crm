<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Pages;

use App\Filament\Resources\FeDeeplinkApplications\FeDeeplinkApplicationResource;
use App\Filament\Resources\FeDeeplinkApplications\Schemas\FeDeeplinkApplicationForm;
use App\Models\Application;
use App\Models\SalesProject;
use App\Support\SalesLineSnapshot;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CreateFeDeeplinkApplication extends CreateRecord
{
    protected static string $resource = FeDeeplinkApplicationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tạo hồ sơ FE Deeplink';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $project = SalesProject::query()->where('slug', 'fe-deeplink')->where('is_active', true)->firstOrFail();
        $creatorId = (int) $data['created_by_id'];
        $application = new Application([
            'sales_project_id' => $project->getKey(),
            'application_code' => $data['application_code'],
            'applicant_name' => $data['applicant_name'],
            'phone' => $data['phone'],
            'status' => 'approved',
            'assigned_sale_id' => $creatorId,
            'created_by_id' => $creatorId,
            'payload' => FeDeeplinkApplicationForm::normalizePayload($data['payload'] ?? []),
            ...SalesLineSnapshot::hierarchyForUserId($creatorId),
        ]);
        $application->setCreatedAt(CarbonImmutable::parse((string) $data['created_at']));
        $application->save();

        return $application;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Tạo hồ sơ')
            ->icon(Heroicon::OutlinedDocumentPlus);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
