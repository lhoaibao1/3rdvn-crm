<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCampaign;
use App\Models\AffiliateConversion;
use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\LosAffiliateConversionPresenter;
use App\Support\LosApplicationPresenter;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Shuchkin\SimpleXLSXGen;

class LosApplicationManagementController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $perPage = in_array((int) $request->input('per_page', 20), [10, 20, 50, 100], true)
            ? (int) $request->input('per_page', 20)
            : 20;

        $data = $this->getFilteredData($request);
        $combined = $data['records'];
        $totalMetrics = $data['stats'];

        $page = (int) $request->input('page', 1);
        $totalItems = $combined->count();
        $pagedItems = $combined->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pagedItems,
            $totalItems,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $availableProjects = $data['available_projects'];
        $availableSales = $data['available_sales'];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pagedItems,
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
                'stats' => $totalMetrics,
                'checksum' => md5(json_encode($pagedItems) . json_encode($totalMetrics)),
                'timestamp' => now()->format('H:i:s d/m/Y'),
            ]);
        }

        return view('los.management.index', [
            'applications' => $pagedItems,
            'paginator' => $paginator,
            'projects' => $availableProjects,
            'sales' => $availableSales,
            'stats' => $totalMetrics,
            'keyword' => trim((string) $request->input('keyword', '')),
            'system' => trim((string) $request->input('system', 'all')),
            'project' => trim((string) $request->input('project', 'all')),
            'status' => trim((string) $request->input('status', 'all')),
            'saleId' => trim((string) $request->input('sale_id', 'all')),
            'dateType' => trim((string) $request->input('date_type', 'created')),
            'dateRange' => trim((string) $request->input('date_range', 'all')),
            'dateFrom' => trim((string) $request->input('date_from', '')),
            'dateTo' => trim((string) $request->input('date_to', '')),
            'perPage' => $perPage,
        ]);
    }

    /**
     * Xuất báo cáo danh sách hồ sơ dạng Microsoft Excel (.xlsx) chuẩn theo bộ lọc
     */
    public function export(Request $request)
    {
        $data = $this->getFilteredData($request);
        $records = $data['records'];

        $filename = 'Bao_cao_ho_so_LOS_' . date('Ymd_His') . '.xlsx';

        $rows = [
            [
                '<b>STT</b>',
                '<b>Mã Hồ sơ / Giao dịch</b>',
                '<b>Nguồn / Kênh</b>',
                '<b>Dự án / Chiến dịch</b>',
                '<b>Sản phẩm / Gói vay</b>',
                '<b>Họ và tên khách hàng</b>',
                '<b>Số CCCD / CMND</b>',
                '<b>Số điện thoại</b>',
                '<b>Khoản vay đề xuất (VNĐ)</b>',
                '<b>Khoản vay phê duyệt (VNĐ)</b>',
                '<b>NVKD / Phụ trách</b>',
                '<b>Trạng thái CRM</b>',
                '<b>Lý do / Phản hồi chi tiết</b>',
                '<b>Ngày tạo</b>',
                '<b>Ngày cập nhật</b>',
            ],
        ];

        $stt = 1;
        foreach ($records as $r) {
            $reason = '-';
            if (!empty($r['application_fields'])) {
                foreach ($r['application_fields'] as $f) {
                    if (str_contains($f['label'], 'Lý do') || str_contains($f['label'], 'Phản hồi') || str_contains($f['label'], 'Thông điệp')) {
                        if ($f['value'] !== '-') {
                            $reason = $f['value'];
                            break;
                        }
                    }
                }
            }

            $source = str_starts_with((string)$r['application_code'], 'DG') || str_starts_with((string)$r['application_code'], 'CONV-')
                ? 'Tiếp thị liên kết (Affiliate)'
                : (str_starts_with((string)$r['application_code'], 'FEDL-') ? 'FEOL Deeplink' : 'Hồ sơ CRM LOS');

            $rows[] = [
                $stt++,
                (string) ($r['application_code'] ?? '-'),
                $source,
                (string) ($r['project'] ?? '-'),
                (string) ($r['scheme_or_product'] ?? ($r['product'] ?? '-')),
                (string) ($r['applicant_name'] ?? '-'),
                (string) ($r['identity_number'] ?? '-'),
                (string) ($r['phone_number'] ?? '-'),
                !empty($r['requested_loan_amount']) ? number_format($r['requested_loan_amount'], 0, ',', '.') : '-',
                !empty($r['approved_loan_amount']) ? number_format($r['approved_loan_amount'], 0, ',', '.') : '-',
                (string) ($r['creator'] ?? '-'),
                (string) ($r['status_label'] ?? '-'),
                $reason,
                (string) ($r['created_at'] ?? '-'),
                (string) ($r['updated_at'] ?? '-'),
            ];
        }

        SimpleXLSXGen::fromArray($rows)
            ->setDefaultFont('Segoe UI')
            ->setDefaultFontSize(11)
            ->downloadAs($filename);
        exit;
    }

    private function getFilteredData(Request $request): array
    {
        $user = $request->user() ?: auth()->user();
        if (! $user) {
            $user = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first() ?: User::first();
        }

        $keyword = trim((string) $request->input('keyword', ''));
        $system = trim((string) $request->input('system', 'all'));
        $projectSlug = trim((string) $request->input('project', 'all'));
        $status = trim((string) $request->input('status', 'all'));
        $saleId = trim((string) $request->input('sale_id', 'all'));
        $dateType = trim((string) $request->input('date_type', 'created'));
        $dateRange = trim((string) $request->input('date_range', 'all'));
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        $dateColumn = ($dateType === 'updated') ? 'updated_at' : 'created_at';
        $isAdmin = $user ? $user->hasAnyRole(['Admin', 'Sales Admin', 'super_admin']) : true;

        // Scoped user codes for Affiliate scoping
        $scopedUserCodes = $user ? collect([$user->employee_code, $user->uid, (string)$user->id])
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all() : [];

        if (! $isAdmin) {
            $subordinatesQuery = User::query();
            if ($user->hasRole('Team Leader')) {
                $subordinatesQuery->where('team_leader_id', $user->id);
            } elseif ($user->hasRole('AM')) {
                $subordinatesQuery->where(function (Builder $q) use ($user): void {
                    $q->where('am_id', $user->id)
                        ->orWhereHas('teamLeader', fn ($lq) => $lq->where('am_id', $user->id));
                });
            } elseif ($user->hasRole('ZD')) {
                $subordinatesQuery->where(function (Builder $q) use ($user): void {
                    $q->where('zd_id', $user->id)
                        ->orWhereHas('am', fn ($aq) => $aq->where('zd_id', $user->id))
                        ->orWhereHas('teamLeader', fn ($lq) => $lq->where('zd_id', $user->id));
                });
            }

            $subCodes = $subordinatesQuery->get(['employee_code', 'uid', 'id'])
                ->flatMap(fn ($u) => array_filter([$u->employee_code, $u->uid, (string)$u->id]))
                ->values()
                ->all();

            $scopedUserCodes = array_values(array_unique(array_merge($scopedUserCodes, $subCodes)));
        }

        // ══════════════════════════════════════════════════════
        // 1. BASE SCOPED QUERIES FOR OVERALL METRICS BY PROJECT
        // ══════════════════════════════════════════════════════
        $appBaseQuery = Application::query()
            ->with([
                'salesProject:id,name,slug',
                'createdBy:id,name,uid,employee_code',
                'assignedSale:id,name,uid,employee_code',
                'team:id,name',
                'teamLeader:id,name',
                'lead:id,payload',
                'feolIntegration',
            ]);

        RecordVisibility::applyUserScope($appBaseQuery, $user, 'assigned_sale_id', 'assignedSale');

        if (! $isAdmin) {
            $allowedProjectSlugs = SalesProjectAccess::userProjectSlugs($user);
            if (! empty($allowedProjectSlugs)) {
                $appBaseQuery->whereHas('salesProject', function (Builder $pq) use ($allowedProjectSlugs): void {
                    $pq->whereIn('slug', $allowedProjectSlugs);
                });
            }
        }

        $affBaseQuery = AffiliateConversion::query()
            ->with('createdBy:id,name,uid,employee_code');

        if (! $isAdmin) {
            $affBaseQuery->where(function (Builder $q) use ($scopedUserCodes, $user): void {
                $q->whereIn('aff_sub1', $scopedUserCodes)
                    ->orWhere('created_by_id', $user->id);
            });
        }

        // Project filter on Base Queries if user chooses a specific project
        $appStatsQuery = clone $appBaseQuery;
        $affStatsQuery = clone $affBaseQuery;

        if ($projectSlug !== 'all' && $projectSlug !== '') {
            $clean = str_replace(['-', '_'], '', strtolower($projectSlug));
            $appStatsQuery->whereHas('salesProject', function (Builder $pq) use ($projectSlug, $clean): void {
                $pq->where('slug', $projectSlug)->orWhere('name', 'ilike', "%{$projectSlug}%");
                if (str_contains($clean, 'fe')) {
                    $pq->orWhere('slug', 'fe-deeplink');
                } elseif (str_contains($clean, 'lotte')) {
                    $pq->orWhere('slug', 'lotte-finance');
                } elseif (str_contains($clean, 'acl')) {
                    $pq->orWhere('slug', 'acl-mix');
                }
            });
            $affStatsQuery->where(function (Builder $q) use ($projectSlug, $clean): void {
                $q->where('offer_id', 'ilike', "%{$projectSlug}%")
                  ->orWhere('campaign_name', 'ilike', "%{$projectSlug}%")
                  ->orWhere('partner', 'ilike', "%{$projectSlug}%");
                if (str_contains($clean, 'shb')) {
                    $q->orWhere('offer_id', 'ilike', '%shb%')->orWhere('campaign_name', 'ilike', '%shb%');
                } elseif (str_contains($clean, 'vpbank')) {
                    $q->orWhere('offer_id', 'ilike', '%vpbank%')->orWhere('campaign_name', 'ilike', '%vpbank%');
                } elseif (str_contains($clean, 'tinvay')) {
                    $q->orWhere('offer_id', 'ilike', '%tinvay%')->orWhere('campaign_name', 'ilike', '%tinvay%');
                }
            });
        }

        if ($system === 'internal') {
            $affStatsQuery->whereRaw('1 = 0');
            $appStatsQuery->whereDoesntHave('salesProject', fn ($pq) => $pq->where('slug', 'fe-deeplink'))
                ->whereDoesntHave('feolIntegration');
        } elseif ($system === 'feol') {
            $affStatsQuery->whereRaw('1 = 0');
            $appStatsQuery->where(function (Builder $q): void {
                $q->whereHas('salesProject', fn ($pq) => $pq->where('slug', 'fe-deeplink'))
                    ->orWhereHas('feolIntegration');
            });
        } elseif ($system === 'affiliate') {
            $appStatsQuery->whereRaw('1 = 0');
        }

        // Stats calculation by project scope
        $appRecordsForStats = $appStatsQuery->get()->map(fn (Application $a) => LosApplicationPresenter::make($a));
        $affRecordsForStats = $affStatsQuery->get()->map(fn (AffiliateConversion $c) => LosAffiliateConversionPresenter::make($c));
        $allProjectScopeRecords = $appRecordsForStats->concat($affRecordsForStats);

        $totalCount = $allProjectScopeRecords->count();
        $processingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $approvedAmount = 0.0;
        $requestedAmount = 0.0;

        foreach ($allProjectScopeRecords as $item) {
            $tone = $item['status_tone'] ?? 'primary';
            if ($tone === 'success') {
                $approvedCount++;
            } elseif ($tone === 'danger') {
                $rejectedCount++;
            } else {
                $processingCount++;
            }

            if (!empty($item['approved_loan_amount'])) {
                $approvedAmount += (float)$item['approved_loan_amount'];
            }
            if (!empty($item['requested_loan_amount'])) {
                $requestedAmount += (float)$item['requested_loan_amount'];
            }
        }

        $approvalRate = $totalCount > 0 ? round(($approvedCount / $totalCount) * 100, 1) : 0;
        $processingRate = $totalCount > 0 ? round(($processingCount / $totalCount) * 100, 1) : 0;
        $rejectedRate = $totalCount > 0 ? round(($rejectedCount / $totalCount) * 100, 1) : 0;

        $totalMetrics = [
            'total' => $totalCount,
            'processing' => $processingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'approved_amount' => $approvedAmount,
            'requested_amount' => $requestedAmount,
            'approval_rate' => $approvalRate,
            'processing_rate' => $processingRate,
            'rejected_rate' => $rejectedRate,
        ];

        // ══════════════════════════════════════════════════════
        // 2. FILTERED LIST QUERIES (FOR TABLE & EXPORT)
        // ══════════════════════════════════════════════════════
        $applicationsCollection = collect();

        if ($system === 'all' || $system === 'internal' || $system === 'feol') {
            $appQuery = clone $appBaseQuery;

            if ($system === 'feol') {
                $appQuery->where(function (Builder $q): void {
                    $q->whereHas('salesProject', fn ($pq) => $pq->where('slug', 'fe-deeplink'))
                        ->orWhereHas('feolIntegration');
                });
            } elseif ($system === 'internal') {
                $appQuery->where(function (Builder $q): void {
                    $q->whereDoesntHave('salesProject', fn ($pq) => $pq->where('slug', 'fe-deeplink'))
                        ->whereDoesntHave('feolIntegration');
                });
            }

            if ($keyword !== '') {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                $numericOnly = preg_replace('/[^0-9]/', '', $keyword);
                $identityNumber = (strlen($numericOnly) === 9 || strlen($numericOnly) === 12) ? $numericOnly : '';

                $appQuery->where(function (Builder $q) use ($keyword, $keywordLower, $numericOnly, $identityNumber): void {
                    $q->whereRaw('LOWER(TRIM(application_code)) = ?', [$keywordLower])
                        ->orWhereRaw('LOWER(application_code) LIKE ?', ["%{$keywordLower}%"])
                        ->orWhereRaw('LOWER(applicant_name) LIKE ?', ["%{$keywordLower}%"])
                        ->orWhereRaw("LOWER(COALESCE(payload->'fields'->>'customer_name', '')) LIKE ?", ["%{$keywordLower}%"])
                        ->orWhereRaw("LOWER(COALESCE(payload->'fields'->>'applicant_name', '')) LIKE ?", ["%{$keywordLower}%"])
                        ->orWhereRaw("LOWER(COALESCE(payload->'module_fields'->>'applicant_name', '')) LIKE ?", ["%{$keywordLower}%"])
                        ->orWhereHas('feolIntegration', function (Builder $fq) use ($keyword, $keywordLower): void {
                            $fq->where('partner_lead_id', 'like', "%{$keyword}%")
                                ->orWhere('partner_app_id', 'like', "%{$keyword}%")
                                ->orWhere('partner_request_id', 'like', "%{$keyword}%")
                                ->orWhereRaw("LOWER(COALESCE(raw_payload->>'app_id', '')) LIKE ?", ["%{$keywordLower}%"])
                                ->orWhereRaw("LOWER(COALESCE(raw_payload->>'customer_name', '')) LIKE ?", ["%{$keywordLower}%"])
                                ->orWhereRaw("LOWER(COALESCE(raw_payload->>'sale_code', '')) LIKE ?", ["%{$keywordLower}%"]);
                        });

                    if ($identityNumber !== '') {
                        $q->orWhere('identity_number', $identityNumber)
                            ->orWhereRaw("regexp_replace(COALESCE(identity_number, ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'identity_number', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'cccd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'module_fields'->>'identity_number', ''), '[^0-9]', '', 'g') = ?", [$identityNumber]);
                    }

                    if ($numericOnly !== '') {
                        $q->orWhere('phone', 'like', "%{$numericOnly}%")
                            ->orWhere('identity_number', 'like', "%{$numericOnly}%")
                            ->orWhereRaw("regexp_replace(COALESCE(identity_number, ''), '[^0-9]', '', 'g') = ?", [$numericOnly])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'phone', ''), '[^0-9]', '', 'g') = ?", [$numericOnly])
                            ->orWhereHas('feolIntegration', function (Builder $fq) use ($numericOnly): void {
                                $fq->whereRaw("regexp_replace(COALESCE(raw_payload->>'customer_mobile', ''), '[^0-9]', '', 'g') LIKE ?", ["%{$numericOnly}%"]);
                            });
                    }
                });
            }

            if ($projectSlug !== 'all' && $projectSlug !== '') {
                $clean = str_replace(['-', '_'], '', strtolower($projectSlug));
                $appQuery->whereHas('salesProject', function (Builder $pq) use ($projectSlug, $clean): void {
                    $pq->where('slug', $projectSlug)
                        ->orWhere('name', 'ilike', "%{$projectSlug}%");
                    if (str_contains($clean, 'fe')) {
                        $pq->orWhere('slug', 'fe-deeplink');
                    } elseif (str_contains($clean, 'lotte')) {
                        $pq->orWhere('slug', 'lotte-finance');
                    } elseif (str_contains($clean, 'acl')) {
                        $pq->orWhere('slug', 'acl-mix');
                    }
                });
            }

            if ($status !== 'all' && $status !== '') {
                if ($status === 'approved') {
                    $appQuery->where(function (Builder $q): void {
                        $q->where('status', 'like', '%disburs%')
                            ->orWhere('status', 'like', '%approved%')
                            ->orWhere('status', 'completed');
                    });
                } elseif ($status === 'rejected') {
                    $appQuery->where(function (Builder $q): void {
                        $q->where('status', 'like', '%reject%')
                            ->orWhere('status', 'like', '%cancel%')
                            ->orWhere('status', 'ineligible')
                            ->orWhere('status', 'hard_reject');
                    });
                } elseif ($status === 'pending') {
                    $appQuery->where(function (Builder $q): void {
                        $q->where('status', 'not like', '%disburs%')
                            ->where('status', 'not like', '%reject%')
                            ->where('status', 'not like', '%cancel%')
                            ->where('status', '!=', 'ineligible')
                            ->where('status', '!=', 'hard_reject')
                            ->where('status', '!=', 'completed');
                    });
                } else {
                    $appQuery->where('status', $status);
                }
            }

            if ($saleId !== 'all' && is_numeric($saleId)) {
                $appQuery->where('assigned_sale_id', (int) $saleId);
            }

            $this->applyDateFilter($appQuery, $dateRange, $dateFrom, $dateTo, $dateColumn);

            $applicationsCollection = $appQuery->latest($dateColumn)->get()
                ->map(fn (Application $app): array => LosApplicationPresenter::make($app));
        }

        // Affiliate Query
        $affiliatesCollection = collect();

        if ($system === 'all' || $system === 'affiliate') {
            $affQuery = clone $affBaseQuery;

            if ($keyword !== '') {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                $numericOnly = preg_replace('/[^0-9]/', '', $keyword);
                $identityNumber = (strlen($numericOnly) === 9 || strlen($numericOnly) === 12) ? $numericOnly : '';

                $affQuery->where(function (Builder $q) use ($keyword, $keywordLower, $numericOnly, $identityNumber): void {
                    $q->whereRaw('LOWER(TRIM(conversion_id)) = ?', [$keywordLower])
                        ->orWhereRaw('LOWER(TRIM(transaction_id)) = ?', [$keywordLower])
                        ->orWhereRaw('LOWER(TRIM(click_id)) = ?', [$keywordLower])
                        ->orWhereRaw('LOWER(TRIM(aff_sub1)) = ?', [$keywordLower])
                        ->orWhereRaw('LOWER(conversion_id) LIKE ?', ["%{$keywordLower}%"])
                        ->orWhereRaw('LOWER(transaction_id) LIKE ?', ["%{$keywordLower}%"])
                        ->orWhereRaw('LOWER(product_name) LIKE ?', ["%{$keywordLower}%"])
                        ->orWhereRaw('LOWER(campaign_name) LIKE ?', ["%{$keywordLower}%"]);

                    if ($identityNumber !== '') {
                        $q->orWhere('aff_sub4', $identityNumber)
                            ->orWhereRaw("regexp_replace(COALESCE(aff_sub4, ''), '[^0-9]', '', 'g') = ?", [$identityNumber]);
                    }

                    if ($numericOnly !== '') {
                        $q->orWhere('aff_sub2', 'like', "%{$numericOnly}%")
                            ->orWhere('aff_sub3', 'like', "%{$numericOnly}%")
                            ->orWhere('aff_sub4', 'like', "%{$numericOnly}%")
                            ->orWhere('transaction_id', 'like', "%{$numericOnly}%");
                    }
                });
            }

            if ($projectSlug !== 'all' && $projectSlug !== '') {
                $clean = str_replace(['-', '_'], '', strtolower($projectSlug));
                $affQuery->where(function (Builder $q) use ($projectSlug, $clean): void {
                    $q->where('offer_id', 'ilike', "%{$projectSlug}%")
                        ->orWhere('campaign_name', 'ilike', "%{$projectSlug}%")
                        ->orWhere('partner', 'ilike', "%{$projectSlug}%");
                    if (str_contains($clean, 'shb')) {
                        $q->orWhere('offer_id', 'ilike', '%shb%')->orWhere('campaign_name', 'ilike', '%shb%');
                    } elseif (str_contains($clean, 'vpbank')) {
                        $q->orWhere('offer_id', 'ilike', '%vpbank%')->orWhere('campaign_name', 'ilike', '%vpbank%');
                    } elseif (str_contains($clean, 'tinvay')) {
                        $q->orWhere('offer_id', 'ilike', '%tinvay%')->orWhere('campaign_name', 'ilike', '%tinvay%');
                    }
                });
            }

            if ($status !== 'all' && $status !== '') {
                $statusMap = match ($status) {
                    'pending' => ['pending', '0'],
                    'approved', 'success' => ['approved', 'success', 'confirmed', 'paid', '1'],
                    'rejected', 'cancelled' => ['rejected', 'cancelled', 'canceled', 'failed', '-1'],
                    default => [$status],
                };
                $affQuery->where(function (Builder $q) use ($statusMap, $status): void {
                    $q->whereIn('conversion_status', $statusMap)
                        ->orWhereIn('conversion_status_code', $statusMap)
                        ->orWhere('conversion_status', 'ilike', "%{$status}%");
                });
            }

            if ($saleId !== 'all' && is_numeric($saleId)) {
                $affQuery->where('created_by_id', (int) $saleId);
            }

            $this->applyDateFilter($affQuery, $dateRange, $dateFrom, $dateTo, $dateColumn);

            $affiliatesCollection = $affQuery->latest($dateColumn)->get()
                ->map(fn (AffiliateConversion $conv): array => LosAffiliateConversionPresenter::make($conv));
        }

        $combined = $applicationsCollection->concat($affiliatesCollection)
            ->sortByDesc(fn ($item) => $dateType === 'updated' ? ($item['updated_timestamp'] ?? 0) : ($item['created_timestamp'] ?? ($item['updated_timestamp'] ?? 0)))
            ->values();

        // Available Projects & Sales
        $salesProjects = SalesProject::query()->orderBy('name');
        if (! $isAdmin) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);
            if (! empty($slugs)) {
                $salesProjects->whereIn('slug', $slugs);
            }
        }
        $salesProjectsList = $salesProjects->get(['id', 'name', 'slug'])
            ->map(fn ($p) => ['slug' => $p->slug, 'name' => $p->name])
            ->toArray();

        $affCampaignsList = AffiliateCampaign::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => [
                'slug' => $c->slug,
                'name' => $c->name . ' (Tiếp thị)',
            ])
            ->toArray();

        $affDynamicOffers = AffiliateConversion::query()
            ->select('offer_id', 'campaign_name')
            ->whereNotNull('offer_id')
            ->distinct()
            ->get()
            ->map(function ($c) {
                $slug = (string) $c->offer_id;
                $name = $c->campaign_name ?: strtoupper($slug);
                if (str_contains(strtolower($slug), 'shb')) {
                    $slug = 'shb-finance';
                    $name = 'SHB Finance';
                } elseif (str_contains(strtolower($slug), 'vpbank')) {
                    $slug = 'vpbank-upl';
                    $name = 'VPBank UPL';
                } elseif (str_contains(strtolower($slug), 'tinvay')) {
                    $slug = 'tinvay';
                    $name = 'Tin Vay';
                }

                return [
                    'slug' => $slug,
                    'name' => $name . ' (Tiếp thị)',
                ];
            })
            ->toArray();

        $availableProjects = array_values(
            collect($salesProjectsList)
                ->concat($affCampaignsList)
                ->concat($affDynamicOffers)
                ->unique('slug')
                ->values()
                ->all()
        );

        $salesQuery = User::query()->orderBy('name');
        if (! $isAdmin) {
            if ($user->hasRole('Team Leader')) {
                $salesQuery->where(function (Builder $q) use ($user): void {
                    $q->where('team_leader_id', $user->id)
                        ->orWhere('id', $user->id);
                });
            } elseif ($user->hasRole('AM')) {
                $salesQuery->where(function (Builder $q) use ($user): void {
                    $q->where('am_id', $user->id)
                        ->orWhere('id', $user->id);
                });
            } elseif ($user->hasRole('ZD')) {
                $salesQuery->where(function (Builder $q) use ($user): void {
                    $q->where('zd_id', $user->id)
                        ->orWhere('id', $user->id);
                });
            } else {
                $salesQuery->where('id', $user->id);
            }
        }
        $availableSales = $salesQuery->get(['id', 'name', 'uid', 'employee_code']);

        return [
            'records' => $combined,
            'stats' => $totalMetrics,
            'available_projects' => $availableProjects,
            'available_sales' => $availableSales,
        ];
    }

    private function applyDateFilter(Builder $query, string $dateRange, string $dateFrom, string $dateTo, string $dateColumn = 'created_at'): void
    {
        if ($dateFrom !== '' && $dateTo !== '') {
            $query->whereBetween($dateColumn, [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom !== '') {
            $query->where($dateColumn, '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif ($dateTo !== '') {
            $query->where($dateColumn, '<=', Carbon::parse($dateTo)->endOfDay());
        } elseif ($dateRange === 'today') {
            $query->whereDate($dateColumn, Carbon::today());
        } elseif ($dateRange === 'yesterday') {
            $query->whereDate($dateColumn, Carbon::yesterday());
        } elseif ($dateRange === '7days') {
            $query->where($dateColumn, '>=', Carbon::now()->subDays(7));
        } elseif ($dateRange === '30days') {
            $query->where($dateColumn, '>=', Carbon::now()->subDays(30));
        } elseif ($dateRange === 'this_month') {
            $query->whereMonth($dateColumn, Carbon::now()->month)
                ->whereYear($dateColumn, Carbon::now()->year);
        } elseif ($dateRange === 'last_month') {
            $query->whereMonth($dateColumn, Carbon::now()->subMonth()->month)
                ->whereYear($dateColumn, Carbon::now()->subMonth()->year);
        }
    }
}
