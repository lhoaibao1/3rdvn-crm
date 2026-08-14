<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\SalesProject;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompletedCustomerDirectoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('services.vpn_directory.token', '');
        $provided = (string) $request->bearerToken();
        abort_unless($expected !== '' && $provided !== '' && hash_equals($expected, $provided), 401, 'Invalid integration token.');

        $projectSlug = trim((string) $request->query('project_slug', ''));
        $perPage = min(1000, max(1, (int) $request->integer('per_page', 500)));
        $applications = Application::query()
            ->with('salesProject:id,name,slug')
            ->where(function ($query): void {
                $query->whereIn('status', [AclMixWorkflow::COMPLETED, LotteFinanceWorkflow::DISBURSED])
                    ->orWhere(function ($feQuery): void {
                        $feQuery->where('status', 'approved')
                            ->where(function ($dateQuery): void {
                                $dateQuery->whereNotNull('payload->fields->disbursed_at')
                                    ->orWhereNotNull('payload->fields->completed_at');
                            })
                            ->whereHas('salesProject', fn ($project) => $project->where('slug', 'fe-deeplink'));
                    });
            })
            ->when($projectSlug !== '', fn ($query) => $query->whereHas('salesProject', fn ($project) => $project->where('slug', $projectSlug)))
            ->latest('updated_at')
            ->paginate($perPage);

        $items = $applications->getCollection()->map(function (Application $application): array {
            $payload = $application->payload ?? [];
            $approved = collect([
                data_get($payload, 'review.approved_amount'),
                data_get($payload, 'review.pre_approved_amount'),
                data_get($payload, 'fields.approved_amount'),
                data_get($payload, 'fields.pre_approved_amount'),
                data_get($payload, 'approved_amount'),
            ])->first(fn (mixed $value): bool => filled($value));
            $completedAt = collect([
                data_get($payload, 'fields.disbursed_at'),
                data_get($payload, 'fields.completed_at'),
                data_get($payload, 'workflow.completed_at'),
                data_get($payload, 'workflow.disbursed_at'),
                data_get($payload, 'workflow.last_transition.at'),
                data_get($payload, 'review.disbursed_at'),
                data_get($payload, 'review.approved_at'),
            ])->first(fn (mixed $value): bool => filled($value));

            return [
                'id' => (string) $application->getKey(),
                'application_code' => $application->application_code,
                'project_name' => $application->salesProject?->name ?: 'CRM 3RD',
                'project_slug' => $application->salesProject?->slug,
                'customer_name' => $application->applicant_name ?: data_get($payload, 'fields.customer_name'),
                'phone' => $application->phone ?: data_get($payload, 'fields.phone'),
                'status' => $application->status === LotteFinanceWorkflow::DISBURSED ? 'Đã giải ngân' : 'Hoàn thành',
                'approved_amount' => (int) preg_replace('/[^0-9]/', '', (string) $approved),
                'completed_at' => filled($completedAt) ?
                    \Illuminate\Support\Carbon::parse((string) $completedAt)->toIso8601String() : null,
            ];
        })->values();

        $projects = SalesProject::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active'])
            ->map(fn (SalesProject $project): array => [
                'id' => (string) $project->getKey(),
                'name' => $project->name,
                'slug' => $project->slug,
                'is_active' => (bool) $project->is_active,
            ])
            ->values();

        return response()->json([
            'data' => $items,
            'projects' => $projects,
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }
}
