<?php

namespace App\Http\Controllers;

use App\Enums\FeolSubmitState;
use App\Http\Requests\SubmitFeolLandingRequest;
use App\Jobs\SubmitFeolApplicationToPartner;
use App\Models\Application;
use App\Models\FeolApplicationIntegration;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeolPublicLandingController extends Controller
{
    public function show(string $token): View
    {
        $integration = $this->integration($token);

        return view('feol.landing', [
            'application' => $integration->application,
            'integration' => $integration,
            'submitted' => $integration->submit_state === FeolSubmitState::SUBMITTED,
        ]);
    }

    public function store(SubmitFeolLandingRequest $request, string $token): JsonResponse|RedirectResponse
    {
        $applicationId = DB::transaction(function () use ($request, $token): int {
            $integration = FeolApplicationIntegration::query()
                ->where('public_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(in_array($integration->submit_state, [
                FeolSubmitState::AWAITING_CUSTOMER,
                FeolSubmitState::FAILED,
            ], true), 409, 'Hồ sơ này đã được tiếp nhận và đang xử lý.');

            $application = Application::query()
                ->with('salesProject')
                ->lockForUpdate()
                ->findOrFail($integration->application_id);

            abort_unless($application->salesProject?->slug === 'fe-deeplink', 404);

            $validated = $request->validated();
            $payload = $application->payload ?? [];
            data_set($payload, 'fields.date_of_birth', CarbonImmutable::createFromFormat('d/m/Y', $validated['date_of_birth'])->format('Y-m-d'));
            data_set($payload, 'fields.email', mb_strtolower($validated['email']));
            data_set($payload, 'fields.loan_amount', (int) $validated['loan_amount']);
            data_set($payload, 'fields.loan_term_months', (int) $validated['loan_term_months']);
            data_set($payload, 'fields.customer_consent', true);

            $application->update([
                'applicant_name' => trim($validated['applicant_name']),
                'phone' => $validated['phone'],
                'identity_number' => $validated['identity_number'],
                'payload' => $payload,
            ]);

            $integration->update([
                'submit_state' => FeolSubmitState::QUEUED,
                'consented_at' => now(),
                'submit_ip' => $request->ip(),
                'submit_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'submit_last_error' => null,
            ]);

            $application->changeLogs()->create([
                'actor_id' => null,
                'action' => 'feol_customer_submitted',
                'changes' => [
                    'submit_state' => ['old' => FeolSubmitState::AWAITING_CUSTOMER->value, 'new' => FeolSubmitState::QUEUED->value],
                    'customer_consent' => ['old' => null, 'new' => true],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return (int) $application->getKey();
        }, 3);

        SubmitFeolApplicationToPartner::dispatch($applicationId);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'application_id' => $applicationId,
                'message' => 'CRM đã lưu hồ sơ. Dữ liệu đang được gửi sang FEOL.',
            ], 202);
        }

        return redirect()->route('feol.landing.success', ['token' => $token]);
    }

    public function success(string $token): View
    {
        $integration = $this->integration($token);

        return view('feol.success', compact('integration'));
    }

    private function integration(string $token): FeolApplicationIntegration
    {
        return FeolApplicationIntegration::query()
            ->with(['application.salesProject'])
            ->where('public_token', $token)
            ->whereHas('application.salesProject', fn ($query) => $query->where('slug', 'fe-deeplink'))
            ->firstOrFail();
    }
}
