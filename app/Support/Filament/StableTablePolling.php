<?php

namespace App\Support\Filament;

class StableTablePolling
{
    public static function interval(mixed $livewire, string $interval = '5s'): ?string
    {
        if (! is_object($livewire)) {
            return $interval;
        }

        if (! empty($livewire->selectedTableRecords ?? [])) {
            return null;
        }

        if (! empty($livewire->mountedActions ?? []) || ! empty($livewire->mountedTableActions ?? [])) {
            return null;
        }

        return $interval;
    }
}
