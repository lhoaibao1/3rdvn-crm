<?php

namespace App\Support\Filament;

use App\Models\SalesProject;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;

class ProjectSchemaColumns
{
    /** @return array<int, TextColumn> */
    public static function forLeads(array $excludedKeys = []): array
    {
        $definitions = SalesProject::query()
            ->where('is_active', true)
            ->get(['lead_form_schema'])
            ->flatMap(fn (SalesProject $project): array => $project->lead_form_schema ?? [])
            ->all();

        return self::make($definitions, 'payload.fields', $excludedKeys);
    }

    /** @return array<int, TextColumn> */
    public static function forReports(array $excludedKeys = []): array
    {
        $definitions = SalesProject::query()
            ->where('is_active', true)
            ->get(['lead_form_schema', 'module_form_schema'])
            ->flatMap(fn (SalesProject $project): array => array_merge(
                $project->lead_form_schema ?? [],
                $project->module_form_schema ?? [],
            ))
            ->all();

        return self::make($definitions, 'source_data.data', $excludedKeys);
    }

    public static function forApplication(string $projectSlug, array $excludedKeys = []): array
    {
        $project = SalesProject::query()
            ->where('slug', $projectSlug)
            ->first(['lead_form_schema', 'module_form_schema']);

        if (! $project instanceof SalesProject) {
            return [];
        }

        $seen = array_fill_keys($excludedKeys, true);
        $lead = self::make($project->lead_form_schema ?? [], 'payload.fields', array_keys($seen));

        foreach ($project->lead_form_schema ?? [] as $field) {
            $key = self::key($field);

            if (filled($key)) {
                $seen[$key] = true;
            }
        }

        return [
            ...$lead,
            ...self::make($project->module_form_schema ?? [], 'payload.module_fields', array_keys($seen)),
        ];
    }

    /** @return array<int, TextColumn> */
    private static function make(array $definitions, string $stateRoot, array $excludedKeys): array
    {
        $seen = array_fill_keys($excludedKeys, true);

        return collect($definitions)
            ->filter(fn (mixed $field): bool => is_array($field))
            ->map(function (array $field) use ($stateRoot, &$seen): ?TextColumn {
                $key = self::key($field);

                if (blank($key) || isset($seen[$key])) {
                    return null;
                }

                $seen[$key] = true;
                $displayKey = match ($key) {
                    'province_code' => 'province_name',
                    'district_code' => 'district_name',
                    'ward_code' => 'ward_name',
                    default => str_ends_with($key, '_province_code')
                        || str_ends_with($key, '_district_code')
                        || str_ends_with($key, '_ward_code')
                            ? substr($key, 0, -4).'name'
                            : $key,
                };
                $label = filled($field['label'] ?? null)
                    ? (string) $field['label']
                    : Str::of($key)->replace('_', ' ')->title()->toString();

                return TextColumn::make($stateRoot.'.'.$displayKey)
                    ->label($label)
                    ->placeholder('-')
                    ->limit(50)
                    ->formatStateUsing(fn (mixed $state): string => self::display($state))
                    ->toggleable(isToggledHiddenByDefault: true);
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function key(array $field): string
    {
        return Str::of((string) ($field['field_key'] ?? $field['key'] ?? ''))
            ->snake()
            ->toString();
    }

    private static function display(mixed $state): string
    {
        if (is_bool($state)) {
            return $state ? 'Có' : 'Không';
        }

        if (is_array($state)) {
            return implode(', ', array_map('strval', $state));
        }

        return filled($state) ? (string) $state : '-';
    }
}
