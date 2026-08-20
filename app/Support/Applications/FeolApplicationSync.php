<?php

namespace App\Support\Applications;

use App\Enums\FeDeeplinkStatus;
use App\Enums\FeolSyncState;
use App\Models\Application;
use App\Models\FeolApplicationIntegration;
use App\Support\Notifications\ApplicationNotificationSender;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeolApplicationSync
{
    public function sync(Application $application, array $data): FeolApplicationIntegration
    {
        $incomingError = filled($data['error'] ?? null) ? (string) $data['error'] : null;
        $previousError = $application->feolIntegration?->last_error;
        $previousStatus = null;
        $newStatus = null;

        $integration = DB::transaction(function () use ($application, $data, &$previousStatus, &$newStatus): FeolApplicationIntegration {
            $application = Application::query()
                ->with('salesProject')
                ->lockForUpdate()
                ->findOrFail($application->getKey());

            throw_unless($application->salesProject?->slug === 'fe-deeplink', ValidationException::withMessages([
                'application' => 'Hồ sơ không thuộc dự án FE Deeplink.',
            ]));

            $integration = FeolApplicationIntegration::query()
                ->lockForUpdate()
                ->firstOrNew(['application_id' => $application->getKey()]);
            $before = $integration->exists ? $integration->toArray() : [];
            $pollSeconds = max(5, (int) config('services.feol_bridge.poll_seconds', 5));
            $idlePollSeconds = max($pollSeconds, (int) config('services.feol_bridge.idle_poll_seconds', 10));

            if (filled($data['error'] ?? null)) {
                $isRepeatedError = $integration->exists
                    && $integration->sync_state === FeolSyncState::FAILED
                    && (string) $integration->last_error === (string) $data['error'];

                $integration->fill([
                    'sync_state' => FeolSyncState::FAILED,
                    'last_error' => $data['error'],
                    'last_synced_at' => now(),
                    'next_sync_at' => now()->addSeconds($isRepeatedError ? $idlePollSeconds : $pollSeconds),
                    'raw_payload' => $isRepeatedError ? $integration->raw_payload : ($data['raw_payload'] ?? null),
                    'version' => $isRepeatedError ? $integration->version : ((int) $integration->version) + 1,
                ])->save();

                if (! $isRepeatedError) {
                    $this->audit($application, 'feol_sync_failed', $before, $integration->fresh()->toArray());
                }

                return $integration;
            }

            $status = FeDeeplinkStatus::fromPartnerLabel($data['sub_status'] ?? null);
            $previousStatus = $application->status;

            if (filled($data['sub_status'] ?? null) && ! $status) {
                throw ValidationException::withMessages(['sub_status' => 'Trạng thái FEOL không được hỗ trợ.']);
            }

            $deeplink = $data['deeplink_url'] ?? null;
            if (filled($deeplink) && $status?->rejectsDeeplink()) {
                throw ValidationException::withMessages([
                    'deeplink_url' => 'Deeplink không hợp lệ khi hồ sơ đã bị từ chối hoặc không đủ điều kiện.',
                ]);
            }

            $deeplinkToPersist = match (true) {
                $status?->rejectsDeeplink() => null,
                filled($deeplink) => $deeplink,
                default => $integration->deeplink_url,
            };

            $incomingFingerprint = data_get($data, 'raw_payload._bridge.fingerprint');
            $currentFingerprint = data_get($integration->raw_payload, '_bridge.fingerprint');
            if (
                filled($incomingFingerprint)
                && $incomingFingerprint === $currentFingerprint
                && ($status?->value ?? $integration->sub_status) === $integration->sub_status
                && (($deeplinkToPersist ?: null) === ($integration->deeplink_url ?: null))
            ) {
                $integration->fill([
                    'sync_state' => FeolSyncState::SYNCED,
                    'last_error' => null,
                    'last_synced_at' => now(),
                    'next_sync_at' => $status?->isTerminal() ? null : now()->addSeconds($idlePollSeconds),
                ])->save();

                return $integration;
            }

            $integration->fill([
                'partner_lead_id' => $data['partner_lead_id'] ?? $integration->partner_lead_id,
                'partner_app_id' => $data['partner_app_id'] ?? $integration->partner_app_id,
                'main_status' => $data['main_status'] ?? $integration->main_status,
                'sub_status' => $status?->value ?? $integration->sub_status,
                'b1_url' => $data['b1_url'] ?? $integration->b1_url,
                'deeplink_url' => $deeplinkToPersist,
                'sync_state' => FeolSyncState::SYNCED,
                'last_error' => null,
                'last_synced_at' => now(),
                'next_sync_at' => $status?->isTerminal() ? null : now()->addSeconds($pollSeconds),
                'raw_payload' => $data['raw_payload'] ?? null,
                'version' => ((int) $integration->version) + 1,
            ])->save();

            $payload = $application->payload ?? [];
            foreach ([
                'offer_amount' => 'approved_amount',
                'disbursed_amount' => 'disbursed_amount',
                'topup_amount' => 'topup_amount',
                'insurance_amount' => 'insurance_amount',
                'fee_amount' => 'fee_amount',
                'disbursed_at' => 'disbursed_at',
            ] as $source => $target) {
                if (array_key_exists($source, $data) && $data[$source] !== null) {
                    data_set($payload, "fields.{$target}", $data[$source]);
                }
            }

            if (array_key_exists('product', $data)) {
                $product = collect(['NTB', 'Xsell', 'Topup'])
                    ->first(fn (string $candidate): bool => strcasecmp($candidate, trim((string) ($data['product'] ?? ''))) === 0);

                if ($product === null) {
                    data_forget($payload, 'fields.product');
                } else {
                    data_set($payload, 'fields.product', $product);
                }
            }

            // Financial approval is application data, not integration metadata.
            // Keep one canonical value available to every Application presenter/report.
            if (array_key_exists('offer_amount', $data) && $data['offer_amount'] !== null) {
                data_set($payload, 'review.approved_amount', $data['offer_amount']);
            }

            $application->forceFill([
                'status' => $status?->value ?? $application->status,
                'payload' => $payload,
                'note' => array_key_exists('note', $data) ? $data['note'] : $application->note,
            ])->save();
            $newStatus = $application->status;

            $this->audit($application, 'feol_synced', $before, $integration->fresh()->toArray());

            return $integration;
        }, 3);

        if ($incomingError !== null && $incomingError !== $previousError) {
            ApplicationNotificationSender::integrationFailed($application->fresh(), $incomingError);
        }

        if ($previousStatus !== null && $newStatus !== null && $previousStatus !== $newStatus) {
            $fresh = $application->fresh();
            $status = FeDeeplinkStatus::tryFrom($newStatus);
            if (in_array($status, [FeDeeplinkStatus::ELIGIBLE, FeDeeplinkStatus::INELIGIBLE, FeDeeplinkStatus::PRE_SCREENING_FAILURE], true)) {
                ApplicationNotificationSender::feolEligibilityResult($fresh, $status === FeDeeplinkStatus::ELIGIBLE);
            } else {
                ApplicationNotificationSender::feolStatusChanged($fresh, $previousStatus, $newStatus);
            }
        }

        return $integration;
    }

    private function audit(Application $application, string $action, array $before, array $after): void
    {
        $changes = collect(Arr::except($after, ['created_at', 'updated_at']))
            ->filter(fn (mixed $value, string $key): bool => ($before[$key] ?? null) !== $value)
            ->mapWithKeys(fn (mixed $value, string $key): array => [$key => [
                'old' => $before[$key] ?? null,
                'new' => $value,
            ]])
            ->all();

        if ($changes !== []) {
            $application->changeLogs()->create([
                'actor_id' => auth()->id(),
                'action' => $action,
                'changes' => $changes,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent() ?: 'FEOL Bridge',
            ]);
        }
    }
}
