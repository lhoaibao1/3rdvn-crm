<?php

namespace App\Support\Filament;

use App\Models\TableColumnPreference;
use Illuminate\Support\Str;

class TableColumnPreferences
{
    public static function apply(string $tableKey, array $columns): array
    {
        $order = TableColumnPreference::query()->where('table_key', $tableKey)->value('column_order');

        if (! is_array($order) || $order === []) {
            return $columns;
        }

        $rank = collect($order)
            ->filter(fn (mixed $value): bool => filled($value))
            ->values()
            ->flip()
            ->all();

        return collect($columns)
            ->map(fn (object $column, int $index): array => [
                'column' => $column,
                'index' => $index,
                'key' => self::columnKey($column),
            ])
            ->sortBy(fn (array $item): array => [
                $rank[$item['key']] ?? PHP_INT_MAX,
                $item['index'],
            ])
            ->pluck('column')
            ->values()
            ->all();
    }

    public static function columnKey(object $column): string
    {
        foreach (['getLabel', 'getName'] as $method) {
            if (! method_exists($column, $method)) {
                continue;
            }

            try {
                $value = $column->{$method}();
            } catch (\Throwable) {
                $value = null;
            }

            if (filled($value) && ! is_object($value)) {
                return self::normalize((string) $value);
            }
        }

        return spl_object_hash($column);
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)
            ->squish()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }
}
