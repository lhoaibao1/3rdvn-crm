<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\TableColumnPreference;
use App\Support\Filament\TableColumnPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableColumnPreferenceController extends Controller
{
    private const TABLES = [
        'users',
        'leads',
        'applications.acl-mix',
        'applications.cbp',
        'applications.lotte-finance',
    ];

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('Admin'), 403);

        $data = $request->validate([
            'table_key' => ['required', 'string', Rule::in(self::TABLES)],
            'column_order' => ['required', 'array', 'min:1', 'max:80'],
            'column_order.*' => ['required', 'string', 'max:120'],
        ]);

        $order = collect($data['column_order'])
            ->map(fn (string $value): string => TableColumnPreferences::normalize($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        TableColumnPreference::query()->updateOrCreate(
            ['table_key' => $data['table_key']],
            [
                'column_order' => $order,
                'updated_by_id' => $request->user()->getKey(),
            ],
        );

        return response()->json([
            'ok' => true,
            'table_key' => $data['table_key'],
            'column_order' => $order,
        ]);
    }
}
