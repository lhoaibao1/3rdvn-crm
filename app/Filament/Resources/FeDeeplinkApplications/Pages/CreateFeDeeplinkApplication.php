<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Pages;

use App\Enums\FeDeeplinkStatus;
use App\Enums\FeolSubmitState;
use App\Filament\Resources\FeDeeplinkApplications\FeDeeplinkApplicationResource;
use App\Filament\Resources\FeDeeplinkApplications\Schemas\FeDeeplinkApplicationForm;
use App\Models\Application;
use App\Models\SalesProject;
use App\Enums\FeolSyncState;
use App\Support\Applications\FeolSalesIdentity;
use App\Support\SalesLineSnapshot;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateFeDeeplinkApplication extends CreateRecord
{
    protected static string $resource = FeDeeplinkApplicationResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tạo khách hàng';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $project = SalesProject::query()->where('slug', 'fe-deeplink')->where('is_active', true)->firstOrFail();
        $creator = auth()->user();
        abort_unless($creator, 403);
        $creatorId = (int) $creator->getKey();
        $payload = FeDeeplinkApplicationForm::normalizePayload($data['payload'] ?? []);
        data_set($payload, 'fields.referral_code', app(FeolSalesIdentity::class)->referralCode($creator));
        data_set($payload, 'fields.salesman_code', (string) config('services.feol_bridge.landing_sale_code'));
        $application = new Application([
            'sales_project_id' => $project->getKey(),
            'application_code' => 'FEDL-'.Str::upper((string) Str::ulid()),
            'applicant_name' => $data['applicant_name'],
            'phone' => $data['phone'],
            'identity_number' => $data['identity_number'],
            'status' => FeDeeplinkStatus::PENDING_SUBMISSION->value,
            'assigned_sale_id' => $creatorId,
            'created_by_id' => $creatorId,
            'payload' => $payload,
            ...SalesLineSnapshot::hierarchyForUserId($creatorId),
        ]);
        $application->setCreatedAt(CarbonImmutable::parse((string) $data['created_at']));
        $application->save();
        $publicToken = Str::random(48);
        $application->feolIntegration()->create([
            'public_token' => $publicToken,
            'partner_request_id' => 'FEDL-'.$application->getKey().'-'.Str::upper(Str::random(12)),
            'b1_url' => route('feol.landing.show', ['token' => $publicToken]),
            'submit_state' => FeolSubmitState::AWAITING_CUSTOMER,
            'sync_state' => FeolSyncState::PENDING,
            'sync_requested_at' => now(),
            'next_sync_at' => now(),
        ]);

        return $application;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Tạo khách hàng')
            ->icon(Heroicon::OutlinedDocumentPlus);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
