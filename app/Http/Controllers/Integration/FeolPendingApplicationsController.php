<?php

namespace App\Http\Controllers\Integration;

use App\Enums\FeDeeplinkStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\AuthorizesFeolBridge;
use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeolPendingApplicationsController extends Controller
{
    use AuthorizesFeolBridge;

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeFeolBridge($request);

        $applications = Application::query()
            ->whereHas('salesProject', fn (Builder $query): Builder => $query->where('slug', 'fe-deeplink'))
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('feolIntegration')
                    ->orWhereHas('feolIntegration', fn (Builder $query): Builder => $query
                        ->whereNull('next_sync_at')
                        ->orWhere('next_sync_at', '<=', now())
                        ->orWhereColumn('sync_requested_at', '>', 'last_synced_at'));
            })
            ->whereNotIn('status', collect(FeDeeplinkStatus::cases())->filter->isTerminal()->map->value->all())
            ->with(['createdBy:id,name,uid,employee_code', 'feolIntegration'])
            ->oldest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Application $application): array => [
                'id' => $application->getKey(),
                'internal_code' => $application->application_code,
                'name' => $application->applicant_name,
                'phone' => $application->phone,
                'identity_number' => $application->identity_number,
                'date_of_birth' => data_get($application->payload, 'fields.date_of_birth'),
                'email' => data_get($application->payload, 'fields.email'),
                'loan_amount' => data_get($application->payload, 'fields.loan_amount'),
                'loan_term_months' => data_get($application->payload, 'fields.loan_term_months'),
                'referral_code' => data_get($application->payload, 'fields.referral_code'),
                'customer_consent' => (bool) data_get($application->payload, 'fields.customer_consent'),
                'employee_code' => $application->createdBy?->employee_code ?: $application->createdBy?->uid,
                'partner_lead_id' => $application->feolIntegration?->partner_lead_id,
                'partner_app_id' => $application->feolIntegration?->partner_app_id,
                'current_status' => $application->status,
                'has_b1_url' => filled($application->feolIntegration?->b1_url),
                'has_deeplink_url' => filled($application->feolIntegration?->deeplink_url),
                'partner_fingerprint' => data_get($application->feolIntegration?->raw_payload, '_bridge.fingerprint'),
                'version' => $application->feolIntegration?->version ?? 0,
            ]);

        return response()->json(['data' => $applications]);
    }
}
