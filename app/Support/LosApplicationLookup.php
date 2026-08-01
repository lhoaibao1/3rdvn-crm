<?php

namespace App\Support;

use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LosApplicationLookup
{
    public function search(string $applicationCode = '', string $identityNumber = ''): Collection
    {
        $applicationCode = mb_strtolower(trim($applicationCode));
        $identityNumber = preg_replace('/\D+/', '', $identityNumber) ?: '';

        if ($applicationCode === '' && $identityNumber === '') {
            return collect();
        }

        return Application::query()
            ->with([
                'salesProject:id,name,slug,lead_form_schema,module_form_schema',
                'createdBy:id,name,uid,employee_code',
                'assignedSale:id,name',
                'team:id,name',
                'teamLeader:id,name',
            ])
            ->where(function (Builder $query) use ($applicationCode, $identityNumber): void {
                if ($applicationCode !== '') {
                    $query->whereRaw('LOWER(TRIM(application_code)) = ?', [$applicationCode]);

                    return;
                }

                $query
                    ->whereRaw("regexp_replace(COALESCE(identity_number, ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                    ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'identity_number', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                    ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'cccd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                    ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'cmnd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                    ->orWhereRaw("regexp_replace(COALESCE(payload->'module_fields'->>'cccd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                    ->orWhereRaw("regexp_replace(COALESCE(payload->'module_fields'->>'cmnd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber]);
            })
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (Application $application): array => LosApplicationPresenter::make($application));
    }
}
