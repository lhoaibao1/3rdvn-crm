<?php

namespace App\Support\Applications;

use App\Enums\FeDeeplinkStatus;
use App\Enums\FeolSubmitState;
use App\Enums\FeolSyncState;
use App\Jobs\SubmitFeolApplicationToPartner;
use App\Models\Application;
use App\Models\SalesProject;
use App\Support\SalesLineSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateFeolPublicApplication
{
    public function __construct(private readonly FeolSalesIdentity $identity) {}

    public function handle(
        string $salesCode,
        array $data,
        ?string $ipAddress,
        ?string $userAgent,
    ): Application {
        $sale = $this->identity->userForReferralCode($salesCode);
        $project = SalesProject::query()
            ->where('slug', 'fe-deeplink')
            ->where('is_active', true)
            ->firstOrFail();

        $application = DB::transaction(function () use ($sale, $project, $salesCode, $data, $ipAddress, $userAgent): Application {
            $payload = ['fields' => [
                'date_of_birth' => CarbonImmutable::createFromFormat('d/m/Y', $data['date_of_birth'])->format('Y-m-d'),
                'email' => mb_strtolower(trim($data['email'])),
                'loan_amount' => (int) $data['loan_amount'],
                'loan_term_months' => (int) $data['loan_term_months'],
                'referral_code' => $salesCode,
                'customer_consent' => true,
            ]];

            $application = Application::query()->create([
                'sales_project_id' => $project->getKey(),
                'application_code' => 'FEDL-'.Str::upper((string) Str::ulid()),
                'applicant_name' => trim($data['applicant_name']),
                'phone' => $data['phone'],
                'identity_number' => $data['identity_number'],
                'status' => FeDeeplinkStatus::PENDING_SUBMISSION->value,
                'payload' => $payload,
                ...SalesLineSnapshot::fromUser($sale),
            ]);

            $publicToken = Str::random(48);
            $application->feolIntegration()->create([
                'public_token' => $publicToken,
                'partner_request_id' => 'FEDL-'.$application->getKey().'-'.Str::upper(Str::random(12)),
                'b1_url' => route('feol.landing.show', ['token' => $publicToken]),
                'submit_state' => FeolSubmitState::QUEUED,
                'sync_state' => FeolSyncState::PENDING,
                'sync_requested_at' => now(),
                'next_sync_at' => now(),
                'consented_at' => now(),
                'submit_ip' => $ipAddress,
                'submit_user_agent' => mb_substr((string) $userAgent, 0, 2000),
            ]);

            $application->changeLogs()->create([
                'actor_id' => null,
                'action' => 'feol_public_registration_submitted',
                'changes' => [
                    'referral_code' => ['old' => null, 'new' => $salesCode],
                    'submit_state' => ['old' => null, 'new' => FeolSubmitState::QUEUED->value],
                    'customer_consent' => ['old' => null, 'new' => true],
                ],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return $application->load('feolIntegration');
        }, 3);

        SubmitFeolApplicationToPartner::dispatch((int) $application->getKey());

        return $application;
    }
}
