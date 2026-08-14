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
                        ->where(function (Builder $query): void {
                            $query->where(fn (Builder $query): Builder => $query
                                ->whereNotNull('next_sync_at')
                                ->where('next_sync_at', '<=', now()))
                                ->orWhere(fn (Builder $query): Builder => $query
                                    ->whereNotNull('sync_requested_at')
                                    ->where(fn (Builder $query): Builder => $query
                                        ->whereNull('last_synced_at')
                                        ->orWhereColumn('sync_requested_at', '>', 'last_synced_at')));
                        }))
                    ->orWhere(function (Builder $query): void {
                        $query->whereNotNull('payload->fields->approved_amount')
                            ->where('payload->fields->approved_amount', '>', 0)
                            ->whereHas('feolIntegration', fn (Builder $query): Builder => $query
                                ->whereNotNull('partner_lead_id')
                                ->where(fn (Builder $query): Builder => $query
                                    ->whereNull('last_synced_at')
                                    ->orWhere('last_synced_at', '<=', now()->subMinutes(10))));
                    });
            })
            ->with(['createdBy:id,name,uid,employee_code', 'feolIntegration'])
            ->orderByDesc('updated_at')
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
                'last_error' => $application->feolIntegration?->last_error,
                'version' => $application->feolIntegration?->version ?? 0,
            ]);

        return response()->json(['data' => $applications]);
    }
}
