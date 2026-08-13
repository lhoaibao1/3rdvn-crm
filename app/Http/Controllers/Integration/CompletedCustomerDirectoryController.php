<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\Application;
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
        $items = Application::query()
            ->with('salesProject:id,name,slug')
            ->whereIn('status', [AclMixWorkflow::COMPLETED, LotteFinanceWorkflow::DISBURSED])
            ->when($projectSlug !== '', fn ($query) => $query->whereHas('salesProject', fn ($project) => $project->where('slug', $projectSlug)))
            ->latest('updated_at')
            ->limit(1000)
            ->get()
            ->map(function (Application $application): array {
                $payload = $application->payload ?? [];
                $approved = collect([
                    data_get($payload, 'review.approved_amount'),
                    data_get($payload, 'review.pre_approved_amount'),
                    data_get($payload, 'fields.approved_amount'),
                    data_get($payload, 'fields.pre_approved_amount'),
                    data_get($payload, 'approved_amount'),
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
                    'completed_at' => $application->updated_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json(['data' => $items]);
    }
}
