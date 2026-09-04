<?php

namespace App\Support;

use App\Models\AffiliateCampaign;
use App\Models\AffiliateConversion;
use App\Models\Application;
use App\Models\SalesProject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LosApplicationLookup
{
    public function search(array $filters): Collection
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $projectFilter = trim((string) ($filters['project'] ?? 'all'));
        $systemFilter = trim((string) ($filters['system'] ?? 'all'));
        $statusFilter = trim((string) ($filters['status'] ?? 'all'));
        $dateFilter = trim((string) ($filters['date_range'] ?? 'all'));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        $keywordLower = mb_strtolower($keyword, 'UTF-8');
        $numericOnly = preg_replace('/[^0-9]/', '', $keyword);
        $identityNumber = (strlen($numericOnly) === 9 || strlen($numericOnly) === 12) ? $numericOnly : '';

        $hasSearch = ($keyword !== '');

        $applications = collect();
        $affiliateConversions = collect();

        // ─── 1. Query CRM LOS Applications ───
        if ($systemFilter === 'all' || $systemFilter === 'internal' || $systemFilter === 'feol') {
            $appQuery = Application::query()
                ->with([
                    'salesProject:id,name,slug',
                    'createdBy:id,name,uid,employee_code',
                    'assignedSale:id,name,uid,employee_code',
                    'team:id,name',
                    'teamLeader:id,name',
                    'lead:id,payload',
                    'feolIntegration',
                ]);

            if ($systemFilter === 'feol') {
                $appQuery->where(function (Builder $q): void {
                    $q->whereHas('salesProject', fn ($pq) => $pq->where('slug', 'fe-deeplink'))
                        ->orWhereHas('feolIntegration');
                });
            } elseif ($systemFilter === 'internal') {
                $appQuery->where(function (Builder $q): void {
                    $q->whereDoesntHave('salesProject', fn ($pq) => $pq->where('slug', 'fe-deeplink'))
                        ->whereDoesntHave('feolIntegration');
                });
            }

            if ($hasSearch) {
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
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'module_fields'->>'identity_number', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'module_fields'->>'cccd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'module_fields'->>'cmnd', ''), '[^0-9]', '', 'g') = ?", [$identityNumber]);
                    }

                    if ($numericOnly !== '') {
                        $q->orWhere('phone', 'like', "%{$numericOnly}%")
                            ->orWhere('identity_number', 'like', "%{$numericOnly}%")
                            ->orWhereRaw("regexp_replace(COALESCE(identity_number, ''), '[^0-9]', '', 'g') = ?", [$numericOnly])
                            ->orWhereRaw("regexp_replace(COALESCE(payload->'fields'->>'phone', ''), '[^0-9]', '', 'g') = ?", [$numericOnly])
                            ->orWhereHas('feolIntegration', function (Builder $fq) use ($numericOnly): void {
                                $fq->whereRaw("regexp_replace(COALESCE(raw_payload->>'customer_mobile', ''), '[^0-9]', '', 'g') LIKE ?", ["%{$numericOnly}%"])
                                    ->orWhereRaw("regexp_replace(COALESCE(raw_payload->>'customer_id_no', ''), '[^0-9]', '', 'g') LIKE ?", ["%{$numericOnly}%"]);
                            });
                    }
                });
            }

            if ($projectFilter !== 'all' && $projectFilter !== '') {
                $clean = str_replace(['-', '_'], '', strtolower($projectFilter));
                $appQuery->whereHas('salesProject', function (Builder $pq) use ($projectFilter, $clean): void {
                    $pq->where('slug', $projectFilter)
                        ->orWhere('name', 'ilike', "%{$projectFilter}%");
                    if (str_contains($clean, 'fe')) {
                        $pq->orWhere('slug', 'fe-deeplink');
                    } elseif (str_contains($clean, 'lotte')) {
                        $pq->orWhere('slug', 'lotte-finance');
                    } elseif (str_contains($clean, 'acl')) {
                        $pq->orWhere('slug', 'acl-mix');
                    }
                });
            }

            if ($statusFilter !== 'all' && $statusFilter !== '') {
                $appQuery->where('status', 'ilike', "%{$statusFilter}%");
            }

            $this->applyDateFilter($appQuery, $dateFilter, $dateFrom, $dateTo);

            $applications = $appQuery
                ->latest('updated_at')
                ->limit(30)
                ->get()
                ->map(fn (Application $application): array => LosApplicationPresenter::make($application));
        }

        // ─── 2. Query Affiliate Conversions ───
        if ($systemFilter === 'all' || $systemFilter === 'affiliate') {
            $affQuery = AffiliateConversion::query()
                ->with('createdBy:id,name,uid,employee_code');

            if ($hasSearch) {
                $affQuery->where(function (Builder $q) use ($keyword, $keywordLower, $numericOnly, $identityNumber): void {
                    if ($keyword !== '') {
                        $q->whereRaw('LOWER(TRIM(conversion_id)) = ?', [$keywordLower])
                            ->orWhereRaw('LOWER(TRIM(transaction_id)) = ?', [$keywordLower])
                            ->orWhereRaw('LOWER(TRIM(click_id)) = ?', [$keywordLower])
                            ->orWhereRaw('LOWER(TRIM(aff_sub1)) = ?', [$keywordLower])
                            ->orWhereRaw('LOWER(conversion_id) LIKE ?', ["%{$keywordLower}%"])
                            ->orWhereRaw('LOWER(transaction_id) LIKE ?', ["%{$keywordLower}%"])
                            ->orWhereRaw('LOWER(product_name) LIKE ?', ["%{$keywordLower}%"])
                            ->orWhereRaw('LOWER(campaign_name) LIKE ?', ["%{$keywordLower}%"])
                            ->orWhereRaw("LOWER(TRIM(COALESCE(raw_payload->>'transaction_id', ''))) = ?", [$keywordLower])
                            ->orWhereRaw("LOWER(TRIM(COALESCE(raw_payload->>'conversion_id', ''))) = ?", [$keywordLower]);
                    }

                    if ($identityNumber !== '') {
                        $q->orWhere('aff_sub4', $identityNumber)
                            ->orWhereRaw("regexp_replace(COALESCE(aff_sub4, ''), '[^0-9]', '', 'g') = ?", [$identityNumber])
                            ->orWhereRaw("regexp_replace(COALESCE(raw_payload->>'aff_sub4', ''), '[^0-9]', '', 'g') = ?", [$identityNumber]);
                    }

                    if ($numericOnly !== '') {
                        $q->orWhere('aff_sub2', 'like', "%{$numericOnly}%")
                            ->orWhere('aff_sub3', 'like', "%{$numericOnly}%")
                            ->orWhere('aff_sub4', 'like', "%{$numericOnly}%")
                            ->orWhere('transaction_id', 'like', "%{$numericOnly}%")
                            ->orWhereRaw("regexp_replace(COALESCE(aff_sub3, ''), '[^0-9]', '', 'g') LIKE ?", ["%{$numericOnly}%"]);
                    }
                });
            }

            if ($projectFilter !== 'all' && $projectFilter !== '') {
                $clean = str_replace(['-', '_'], '', strtolower($projectFilter));
                $affQuery->where(function (Builder $q) use ($projectFilter, $clean): void {
                    $q->where('offer_id', 'ilike', "%{$projectFilter}%")
                        ->orWhere('campaign_name', 'ilike', "%{$projectFilter}%")
                        ->orWhere('partner', 'ilike', "%{$projectFilter}%");
                    if (str_contains($clean, 'shb')) {
                        $q->orWhere('offer_id', 'ilike', '%shb%')->orWhere('campaign_name', 'ilike', '%shb%');
                    } elseif (str_contains($clean, 'vpbank')) {
                        $q->orWhere('offer_id', 'ilike', '%vpbank%')->orWhere('campaign_name', 'ilike', '%vpbank%');
                    } elseif (str_contains($clean, 'tinvay')) {
                        $q->orWhere('offer_id', 'ilike', '%tinvay%')->orWhere('campaign_name', 'ilike', '%tinvay%');
                    }
                });
            }

            if ($statusFilter !== 'all' && $statusFilter !== '') {
                $statusMap = match ($statusFilter) {
                    'pending' => ['pending', '0'],
                    'approved', 'success' => ['approved', 'success', 'confirmed', '1'],
                    'rejected', 'cancelled' => ['rejected', 'cancelled', 'canceled', 'failed', '-1'],
                    default => [$statusFilter],
                };
                $affQuery->where(function (Builder $q) use ($statusMap, $statusFilter): void {
                    $q->whereIn('conversion_status', $statusMap)
                        ->orWhereIn('conversion_status_code', $statusMap)
                        ->orWhere('conversion_status', 'ilike', "%{$statusFilter}%");
                });
            }

            $this->applyDateFilter($affQuery, $dateFilter);

            $affiliateConversions = $affQuery
                ->latest('updated_at')
                ->limit(30)
                ->get()
                ->map(fn (AffiliateConversion $conversion): array => LosAffiliateConversionPresenter::make($conversion));
        }

        return $applications->concat($affiliateConversions)
            ->sortByDesc('updated_timestamp')
            ->take(30)
            ->values();
    }

    public function find(string $id): ?array
    {
        $cleanId = trim($id);
        if ($cleanId === '') return null;

        // 1. Prefix checks: CONV-000012 or affiliate-12
        if (str_starts_with($cleanId, 'CONV-')) {
            $realId = (int) substr($cleanId, 5);
            $conv = AffiliateConversion::with('createdBy')->find($realId);
            if ($conv) return LosAffiliateConversionPresenter::make($conv);
        }

        if (str_starts_with($cleanId, 'affiliate-')) {
            $realId = (int) substr($cleanId, 10);
            $conv = AffiliateConversion::with('createdBy')->find($realId);
            if ($conv) return LosAffiliateConversionPresenter::make($conv);
        }

        if (str_starts_with($cleanId, 'APL-')) {
            $realId = (int) substr($cleanId, 4);
            $app = Application::with([
                'salesProject', 'createdBy', 'assignedSale', 'team', 'teamLeader',
                'changeLogs.actor', 'feolIntegration', 'lead'
            ])->find($realId);
            if ($app) return LosApplicationPresenter::make($app);
        }

        // 2. Try direct integer ID lookup
        if (is_numeric($cleanId)) {
            $app = Application::with([
                'salesProject', 'createdBy', 'assignedSale', 'team', 'teamLeader',
                'changeLogs.actor', 'feolIntegration', 'lead'
            ])->find((int)$cleanId);
            if ($app) return LosApplicationPresenter::make($app);

            $conv = AffiliateConversion::with('createdBy')->find((int)$cleanId);
            if ($conv) return LosAffiliateConversionPresenter::make($conv);
        }

        // 3. Try Application Code (APL0200244597, FEDL-..., etc.)
        $appByCode = Application::with([
            'salesProject', 'createdBy', 'assignedSale', 'team', 'teamLeader',
            'changeLogs.actor', 'feolIntegration', 'lead'
        ])
            ->where('application_code', $cleanId)
            ->orWhereRaw('LOWER(TRIM(application_code)) = ?', [mb_strtolower($cleanId, 'UTF-8')])
            ->first();

        if ($appByCode) return LosApplicationPresenter::make($appByCode);

        // 4. Try FE Deeplink partner lead ID / request ID
        $appByFeol = Application::with([
            'salesProject', 'createdBy', 'assignedSale', 'team', 'teamLeader',
            'changeLogs.actor', 'feolIntegration', 'lead'
        ])
            ->whereHas('feolIntegration', function (Builder $fq) use ($cleanId): void {
                $fq->where('partner_lead_id', $cleanId)
                    ->orWhere('partner_app_id', $cleanId)
                    ->orWhere('partner_request_id', $cleanId)
                    ->orWhereRaw("raw_payload->>'app_id' = ?", [$cleanId]);
            })
            ->first();

        if ($appByFeol) return LosApplicationPresenter::make($appByFeol);

        // 5. Try Affiliate Transaction ID, Conversion ID, Click ID
        $convByCode = AffiliateConversion::with('createdBy')
            ->where('transaction_id', $cleanId)
            ->orWhere('conversion_id', $cleanId)
            ->orWhere('click_id', $cleanId)
            ->orWhereRaw('LOWER(TRIM(transaction_id)) = ?', [mb_strtolower($cleanId, 'UTF-8')])
            ->orWhereRaw('LOWER(TRIM(conversion_id)) = ?', [mb_strtolower($cleanId, 'UTF-8')])
            ->first();

        if ($convByCode) return LosAffiliateConversionPresenter::make($convByCode);

        return null;
    }

    public function projects(): array
    {
        $salesList = SalesProject::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($p) => ['slug' => $p->slug ?: (string)$p->id, 'name' => $p->name])
            ->toArray();

        $affCampaigns = AffiliateCampaign::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name . ' (Tiếp thị)'])
            ->toArray();

        $affOffers = AffiliateConversion::query()
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

        return array_values(
            collect($salesList)
                ->concat($affCampaigns)
                ->concat($affOffers)
                ->unique('slug')
                ->values()
                ->all()
        );
    }

    private function applyDateFilter(Builder $query, string $dateFilter, string $dateFrom = '', string $dateTo = ''): void
    {
        if ($dateFrom !== '' && $dateTo !== '') {
            $query->whereBetween('updated_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom !== '') {
            $query->where('updated_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif ($dateTo !== '') {
            $query->where('updated_at', '<=', Carbon::parse($dateTo)->endOfDay());
        } elseif ($dateFilter === 'today') {
            $query->whereDate('updated_at', Carbon::today());
        } elseif ($dateFilter === 'yesterday') {
            $query->whereDate('updated_at', Carbon::yesterday());
        } elseif ($dateFilter === '7days') {
            $query->where('updated_at', '>=', Carbon::now()->subDays(7));
        } elseif ($dateFilter === '30days') {
            $query->where('updated_at', '>=', Carbon::now()->subDays(30));
        } elseif ($dateFilter === 'this_month') {
            $query->whereMonth('updated_at', Carbon::now()->month)
                ->whereYear('updated_at', Carbon::now()->year);
        } elseif ($dateFilter === 'last_month') {
            $query->whereMonth('updated_at', Carbon::now()->subMonth()->month)
                ->whereYear('updated_at', Carbon::now()->subMonth()->year);
        }
    }
}
