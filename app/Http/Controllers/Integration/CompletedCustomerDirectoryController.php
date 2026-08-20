<?php

namespace App\Http\Controllers\Integration;

use App\Enums\FeDeeplinkStatus;
use App\Http\Controllers\Controller;
use App\Models\AffiliateCampaign;
use App\Models\AffiliateConversion;
use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use Carbon\Carbon;
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
        $page = max(1, (int) $request->integer('page', 1));

        // 1. Fetch Approved Application Records
        $appQuery = Application::query()
            ->with([
                'salesProject:id,name,slug',
                'createdBy:id,name,employee_code,uid,team_leader_id',
                'createdBy.teamLeader:id,name,employee_code,uid',
                'teamLeader:id,name,employee_code,uid',
            ])
            ->where(function ($query): void {
                $query->whereIn('status', [AclMixWorkflow::COMPLETED, LotteFinanceWorkflow::DISBURSED])
                    ->orWhere(function ($feQuery): void {
                        $feQuery->where('status', FeDeeplinkStatus::PL_DISBURSED->value)
                            ->where(function ($dateQuery): void {
                                $dateQuery->whereNotNull('payload->fields->disbursed_at')
                                    ->orWhereNotNull('payload->fields->completed_at');
                            })
                            ->whereHas('salesProject', fn ($project) => $project->where('slug', 'fe-deeplink'));
                    });
            })
            ->when($projectSlug !== '', fn ($query) => $query->whereHas('salesProject', fn ($project) => $project->where('slug', $projectSlug)))
            ->latest('updated_at');

        $appItems = $appQuery->get()->map(function (Application $application): array {
            $payload = $application->payload ?? [];
            $approved = collect([
                data_get($payload, 'review.approved_amount'),
                data_get($payload, 'review.pre_approved_amount'),
                data_get($payload, 'fields.approved_amount'),
                data_get($payload, 'fields.pre_approved_amount'),
                data_get($payload, 'approved_amount'),
            ])->first(fn (mixed $value): bool => filled($value));
            $isFeDeeplink = $application->salesProject?->slug === 'fe-deeplink';
            $disbursed = collect($isFeDeeplink ? [
                data_get($payload, 'fields.fee_amount'),
                data_get($payload, 'fields.fee_amt'),
                data_get($payload, 'review.fee_amount'),
                data_get($payload, 'fields.disbursed_amount'),
            ] : [
                data_get($payload, 'fields.disbursed_amount'),
                data_get($payload, 'review.disbursed_amount'),
                data_get($payload, 'workflow.disbursed_amount'),
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
                'customer_name' => $application->applicant_name ?: data_get($payload, 'fields.customer_name', 'Khách hàng CRM'),
                'phone' => $application->phone ?: data_get($payload, 'fields.phone'),
                'app_id' => collect([
                    data_get($payload, 'fields.app_id'),
                    data_get($payload, 'fields.partner_lead_id'),
                    data_get($payload, 'partner.app_id'),
                    $application->application_code,
                ])->first(fn (mixed $value): bool => filled($value)),
                'product' => collect([
                    data_get($payload, 'review.product'),
                    data_get($payload, 'fields.product'),
                    data_get($payload, 'fields.scheme_product'),
                ])->first(fn (mixed $value): bool => filled($value)),
                'created_by_code' => $application->createdBy?->employee_code ?: $application->createdBy?->uid,
                'created_by_name' => $application->createdBy?->name,
                'manager_code' => $application->teamLeader?->employee_code ?: ($application->teamLeader?->uid ?: ($application->createdBy?->teamLeader?->employee_code ?: $application->createdBy?->teamLeader?->uid)),
                'manager_name' => $application->teamLeader?->name ?: $application->createdBy?->teamLeader?->name,
                'status' => ($application->status === LotteFinanceWorkflow::DISBURSED || $isFeDeeplink)
                    ? 'Đã giải ngân'
                    : 'Hoàn thành',
                'approved_amount' => (int) preg_replace('/[^0-9]/', '', (string) $approved),
                'disbursed_amount' => (int) preg_replace('/[^0-9]/', '', (string) $disbursed),
                'completed_at' => filled($completedAt) ?
                    Carbon::parse((string) $completedAt)->toIso8601String() : null,
                'sort_time' => filled($completedAt) ? Carbon::parse((string) $completedAt)->timestamp : $application->updated_at->timestamp,
            ];
        });

        // 2. Fetch Approved Affiliate Conversions (Tiếp thị liên kết: SHB, VPBank, TinVay)
        $usersByCode = User::with('teamLeader')->get()->keyBy('employee_code');
        $usersByUsername = User::with('teamLeader')->get()->keyBy('username');
        $usersById = User::with('teamLeader')->get()->keyBy('id');

        $affQuery = AffiliateConversion::query()
            ->whereIn('conversion_status', ['approved', '1', 'success', 'confirmed', 'paid', 'disbursed', 'completed'])
            ->latest('conversion_time');

        $affItems = $affQuery->get()->map(function (AffiliateConversion $conv) use ($usersByCode, $usersByUsername, $usersById): array {
            $code = trim((string) $conv->aff_sub1);
            $user = $usersByCode->get($code) ?? $usersByUsername->get($code) ?? ($conv->created_by_id ? $usersById->get($conv->created_by_id) : null);

            $campSlug = 'affiliate';
            $campName = 'Tiếp thị liên kết';

            $cLower = strtolower($conv->campaign_name . ' ' . $conv->partner . ' ' . $conv->offer_id);
            if (str_contains($cLower, 'shb')) {
                $campSlug = 'shb-finance';
                $campName = 'SHB Finance';
            } elseif (str_contains($cLower, 'vpbank')) {
                $campSlug = 'vpbank-upl';
                $campName = 'VPBank UPL';
            } elseif (str_contains($cLower, 'tinvay')) {
                $campSlug = 'tinvay';
                $campName = 'Tin Vay';
            }

            $customerName = $conv->customer_name ?: ($conv->applicant_name ?: ('Khách hàng ' . ($conv->transaction_id ?: $conv->conversion_id)));
            $completedAt = $conv->conversion_time ? Carbon::parse($conv->conversion_time)->toIso8601String() : ($conv->updated_at ? $conv->updated_at->toIso8601String() : null);
            $sortTime = $conv->conversion_time ? Carbon::parse($conv->conversion_time)->timestamp : ($conv->updated_at ? $conv->updated_at->timestamp : 0);

            return [
                'id' => 'AFF-' . $conv->id,
                'application_code' => (string) ($conv->conversion_id ?: ('AFF' . $conv->id)),
                'project_name' => $campName,
                'project_slug' => $campSlug,
                'customer_name' => $customerName,
                'phone' => (string) ($conv->phone ?: (data_get($conv->raw_payload, 'phone') ?: '')),
                'app_id' => (string) ($conv->transaction_id ?: $conv->conversion_id),
                'product' => (string) ($conv->product_name ?: $campName),
                'created_by_code' => $user?->employee_code ?: ($user?->uid ?: ($code ?: null)),
                'created_by_name' => $user?->name ?: null,
                'manager_code' => $user?->teamLeader?->employee_code ?: $user?->teamLeader?->uid,
                'manager_name' => $user?->teamLeader?->name,
                'status' => 'Đã giải ngân',
                'approved_amount' => (int) round((float) ($conv->sale_amount ?? 0)),
                'disbursed_amount' => (int) round((float) ($conv->sale_amount ?? 0)),
                'completed_at' => $completedAt,
                'sort_time' => $sortTime,
            ];
        });

        // Filter by projectSlug if specified
        if ($projectSlug !== '') {
            $affItems = $affItems->filter(fn ($item) => $item['project_slug'] === $projectSlug);
        }

        // Combine All Customer Records
        $combinedCollection = $appItems->concat($affItems)->sortByDesc('sort_time')->values();

        // Paginate Combined
        $total = $combinedCollection->count();
        $offset = ($page - 1) * $perPage;
        $items = $combinedCollection->slice($offset, $perPage)->map(function ($item) {
            unset($item['sort_time']);
            return $item;
        })->values();

        $lastPage = max(1, (int) ceil($total / $perPage));

        // 3. Projects Catalog (Sales Projects + Affiliate Campaigns)
        $salesProjects = SalesProject::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active'])
            ->map(fn (SalesProject $project): array => [
                'id' => (string) $project->getKey(),
                'name' => $project->name,
                'slug' => $project->slug,
                'is_active' => (bool) $project->is_active,
            ]);

        $affCampaigns = AffiliateCampaign::query()
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'is_active'])
            ->map(fn (AffiliateCampaign $camp): array => [
                'id' => 'AFF-CAMP-' . $camp->id,
                'name' => $camp->name,
                'slug' => $camp->slug,
                'is_active' => (bool) $camp->is_active,
            ]);

        $projects = $salesProjects->concat($affCampaigns)->unique('slug')->values();

        return response()->json([
            'data' => $items,
            'projects' => $projects,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }
}
