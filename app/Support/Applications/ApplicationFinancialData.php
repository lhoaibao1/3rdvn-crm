<?php

namespace App\Support\Applications;

use App\Models\Application;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ApplicationFinancialData
{
    public static function disbursedAt(Application $application): ?CarbonInterface
    {
        $payload = $application->payload ?? [];
        $terminalTransitionAt = $application->status === LotteFinanceWorkflow::DISBURSED
            ? data_get($payload, 'workflow.last_transition.at')
            : null;
        $value = collect([
            data_get($payload, 'fields.disbursed_at'),
            data_get($payload, 'fields.completed_at'),
            data_get($payload, 'workflow.disbursed_at'),
            data_get($payload, 'workflow.completed_at'),
            data_get($payload, 'review.disbursed_at'),
            $terminalTransitionAt,
        ])->first(fn (mixed $item): bool => filled($item));

        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function product(Application $application): ?string
    {
        return collect([
            data_get($application->payload, 'fields.product'),
            data_get($application->payload, 'fields.scheme_product'),
            data_get($application->payload, 'fields.scheme_name'),
            data_get($application->payload, 'review.product'),
        ])->first(fn (mixed $item): bool => filled($item));
    }

    public static function approvedAmount(Application $application): ?int
    {
        $value = collect([
            data_get($application->payload, 'fields.approved_amount'),
            data_get($application->payload, 'review.approved_amount'),
            data_get($application->payload, 'review.pre_approved_amount'),
        ])->first(fn (mixed $item): bool => filled($item));

        $normalized = preg_replace('/[^0-9]/', '', (string) $value);

        return filled($normalized) ? (int) $normalized : null;
    }
}
