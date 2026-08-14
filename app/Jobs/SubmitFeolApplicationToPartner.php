<?php

namespace App\Jobs;

use App\Enums\FeolSubmitState;
use App\Models\Application;
use App\Support\Applications\FeolPartnerSubmitter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubmitFeolApplicationToPartner implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 45;

    public function __construct(public readonly int $applicationId)
    {
        $this->afterCommit();
        $this->onQueue('integrations');
    }

    public function backoff(): array
    {
        return [5, 15, 30, 60];
    }

    public function handle(FeolPartnerSubmitter $submitter): void
    {
        $application = DB::transaction(function (): ?Application {
            $application = Application::query()
                ->with(['feolIntegration', 'createdBy'])
                ->lockForUpdate()
                ->find($this->applicationId);

            $integration = $application?->feolIntegration;

            if (! $application || ! $integration || $integration->submit_state === FeolSubmitState::SUBMITTED) {
                return null;
            }

            $integration->update([
                'submit_state' => FeolSubmitState::PROCESSING,
                'submit_attempts' => $integration->submit_attempts + 1,
                'partner_last_attempt_at' => now(),
                'submit_last_error' => null,
            ]);

            return $application->fresh(['feolIntegration', 'createdBy']);
        }, 3);

        if (! $application) {
            return;
        }

        try {
            $response = $submitter->submit($application);

            DB::transaction(function () use ($application, $response): void {
                $integration = $application->feolIntegration()->lockForUpdate()->firstOrFail();
                $partnerData = is_array($response['data'] ?? null) ? $response['data'] : [];

                $integration->update([
                    'submit_state' => FeolSubmitState::SUBMITTED,
                    'partner_submitted_at' => now(),
                    'partner_lead_id' => $partnerData['leadId'] ?? $partnerData['lead_id'] ?? $integration->partner_lead_id,
                    'partner_submit_response' => $response,
                    'submit_last_error' => null,
                ]);

                $application->changeLogs()->create([
                    'actor_id' => null,
                    'action' => 'feol_partner_submitted',
                    'changes' => ['submit_state' => ['old' => FeolSubmitState::PROCESSING->value, 'new' => FeolSubmitState::SUBMITTED->value]],
                ]);
            }, 3);
        } catch (Throwable $exception) {
            $application->feolIntegration()->update([
                'submit_state' => FeolSubmitState::FAILED,
                'submit_last_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }
    }
}
