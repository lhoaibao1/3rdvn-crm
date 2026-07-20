<?php

namespace App\Support\Reports;

use App\Models\SalesProject;
use App\Support\LotteFinanceSchemeCatalog;

class ProjectReportProductCatalog
{
    public static function initialOptions(?SalesProject $project): array
    {
        if (! $project instanceof SalesProject) {
            return [];
        }

        if ($project->slug === 'lotte-finance') {
            return LotteFinanceSchemeCatalog::options();
        }

        return self::localOptions($project);
    }

    public static function searchOptions(?SalesProject $project, string $search): array
    {
        if (! $project instanceof SalesProject) {
            return [];
        }

        if ($project->slug === 'lotte-finance') {
            return LotteFinanceSchemeCatalog::searchOptions($search, 150);
        }

        $needle = mb_strtolower(trim($search));

        return collect(self::localOptions($project))
            ->filter(fn (string $label, string $code): bool => $needle === ''
                || str_contains(mb_strtolower($code.' '.$label), $needle))
            ->take(150)
            ->all();
    }

    public static function label(?SalesProject $project, ?string $code): ?string
    {
        if (! $project instanceof SalesProject || blank($code)) {
            return null;
        }

        if ($project->slug === 'lotte-finance') {
            $scheme = LotteFinanceSchemeCatalog::find($code);

            return $scheme === [] ? null : LotteFinanceSchemeCatalog::label($scheme, $code);
        }

        return self::localOptions($project)[$code] ?? null;
    }

    private static function localOptions(SalesProject $project): array
    {
        $configured = collect(array_merge($project->lead_form_schema ?? [], $project->module_form_schema ?? []))
            ->filter(fn (array $field): bool => str_contains(
                mb_strtolower((string) ($field['field_key'] ?? '')),
                'product'
            ) || str_contains(mb_strtolower((string) ($field['field_key'] ?? '')), 'scheme'))
            ->flatMap(fn (array $field): array => collect($field['options'] ?? [])
                ->mapWithKeys(function (mixed $option): array {
                    $value = is_array($option) ? ($option['value'] ?? $option['label'] ?? null) : $option;
                    $value = trim((string) $value);

                    return $value === '' ? [] : [$value => $value];
                })
                ->all())
            ->all();

        return array_replace(match ($project->slug) {
            'acl-mix' => [
                'ACL01' => 'ACL01',
                'ACL02' => 'ACL02',
                'ACL03' => 'ACL03',
                'ACL04' => 'ACL04',
            ],
            'hot-lead' => [
                'LĐTD' => 'LĐTD',
                'Đi làm hưởng lương' => 'Đi làm hưởng lương',
                'Khác' => 'Khác',
            ],
            'cbp' => ['CBP' => 'CBP'],
            default => [],
        }, $configured);
    }
}
