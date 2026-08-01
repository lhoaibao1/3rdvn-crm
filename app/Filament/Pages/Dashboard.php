<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\CbpApplications\CbpApplicationResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\LotteFinanceApplications\LotteFinanceApplicationResource;
use App\Filament\Resources\ProjectReports\ProjectReportResource;
use App\Models\Application;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use App\Support\RoleHierarchy;
use App\Support\UserSpecOptions;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    protected static bool $isDiscovered = false;

    protected static ?string $title = 'Performance';

    protected static ?string $navigationLabel = 'Performance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.dashboard';

    protected Width|string|null $maxContentWidth = Width::Full;

    public int $period = 7;

    private const LEAD_CLOSED_STATUSES = [
        'Từ chối',
        'Khách hàng bị trùng',
        'Khách hàng thoả mãn điều kiện',
        'Đã chuyển Application',
        'rejected',
        'closed',
        'converted',
        'completed',
    ];

    private const APPLICATION_CLOSED_STATUSES = [
        AclMixWorkflow::REJECTED,
        AclMixWorkflow::COMPLETED,
        LotteFinanceWorkflow::REJECTED,
        LotteFinanceWorkflow::UW_REJECTED,
        LotteFinanceWorkflow::UW_FIELD,
        LotteFinanceWorkflow::DISBURSED,
        'rejected',
        'Từ chối',
        'completed',
        'Hoàn thành',
        'post_approval',
    ];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return (bool) ($user?->hasRole('Admin') || $user?->can('dashboard.view'));
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function setPeriod(int $period): void
    {
        $this->period = in_array($period, [7, 30, 90], true) ? $period : 7;
    }

    protected function getViewData(): array
    {
        $period = in_array($this->period, [7, 30, 90], true) ? $this->period : 7;
        $end = CarbonImmutable::now()->endOfDay();
        $start = $end->startOfDay()->subDays($period - 1);
        $previousEnd = $start->subSecond();
        $previousStart = $start->subDays($period);
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $role = RoleHierarchy::primaryRole($user) ?: 'Người dùng';
        $leadQuery = LeadResource::getEloquentQuery();
        $applicationQuery = $this->visibleApplicationQuery();
        $reportQuery = ProjectReportResource::getEloquentQuery();

        $leadCount = (clone $leadQuery)->whereBetween('created_at', [$start, $end])->count();
        $previousLeadCount = (clone $leadQuery)->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $qualifiedLeadCount = (clone $leadQuery)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['Khách hàng thoả mãn điều kiện', 'Đã chuyển Application'])
            ->count();
        $rejectedLeadCount = (clone $leadQuery)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['Từ chối', 'Khách hàng bị trùng'])
            ->count();

        $applicationCount = (clone $applicationQuery)->whereBetween('created_at', [$start, $end])->count();
        $previousApplicationCount = (clone $applicationQuery)->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $activeApplicationCount = (clone $applicationQuery)
            ->whereNotIn('status', [
                AclMixWorkflow::REJECTED,
                AclMixWorkflow::COMPLETED,
                LotteFinanceWorkflow::REJECTED,
                LotteFinanceWorkflow::UW_REJECTED,
                LotteFinanceWorkflow::UW_FIELD,
                LotteFinanceWorkflow::DISBURSED,
                'rejected',
                'Từ chối',
                'completed',
                'Hoàn thành',
                'post_approval',
            ])
            ->count();
        $reportCount = (clone $reportQuery)->whereBetween('created_at', [$start, $end])->count();
        $pendingReportCount = (clone $reportQuery)->whereIn('status', ['pending', 'Chờ xử lý'])->count();
        $unassignedLeadCount = (clone $leadQuery)->whereNull('assigned_sale_id')->count();
        $reviewQueueCount = (clone $applicationQuery)
            ->whereIn('status', [AclMixWorkflow::PENDING_INITIAL_REVIEW, LotteFinanceWorkflow::PRE_CHECK])
            ->count();
        $saleActionRequiredCount = (clone $applicationQuery)
            ->whereIn('status', [
                AclMixWorkflow::SALE_COMPLETION,
                AclMixWorkflow::RETURNED_TO_SALE,
                LotteFinanceWorkflow::SALE_COMPLETION,
                LotteFinanceWorkflow::RETURNED_TO_SALE,
            ])
            ->count();
        $completedApplicationCount = (clone $applicationQuery)
            ->whereBetween('updated_at', [$start, $end])
            ->whereIn('status', [
                AclMixWorkflow::COMPLETED,
                LotteFinanceWorkflow::DISBURSED,
                'completed',
                'Hoàn thành',
                'post_approval',
            ])
            ->count();
        $activeMemberCount = RoleHierarchy::applyVisibilityScope(User::query(), $user)
            ->where('employment_status', User::STATUS_ACTIVE)
            ->count();

        $queueStats = $this->queueStats($leadQuery, $applicationQuery, $start, $end);

        $dashboard = $this->dashboardConfiguration($role, [
            'leadCount' => $leadCount,
            'previousLeadCount' => $previousLeadCount,
            'qualifiedLeadCount' => $qualifiedLeadCount,
            'rejectedLeadCount' => $rejectedLeadCount,
            'applicationCount' => $applicationCount,
            'previousApplicationCount' => $previousApplicationCount,
            'activeApplicationCount' => $activeApplicationCount,
            'pendingReportCount' => $pendingReportCount,
            'reportCount' => $reportCount,
            'unassignedLeadCount' => $unassignedLeadCount,
            'reviewQueueCount' => $reviewQueueCount,
            'saleActionRequiredCount' => $saleActionRequiredCount,
            'completedApplicationCount' => $completedApplicationCount,
            'activeMemberCount' => $activeMemberCount,
            ...$queueStats,
        ]);

        $trend = $this->trendSeries($leadQuery, $applicationQuery, $start, $end, $period);

        $applicationResource = collect([
            ApplicationResource::class,
            CbpApplicationResource::class,
            LotteFinanceApplicationResource::class,
        ])->first(fn (string $resource): bool => $resource::canViewAny());

        $recentLeadQuery = (clone $leadQuery)->with(['salesProject', 'assignedSale']);
        $recentApplicationQuery = (clone $applicationQuery)->with(['salesProject', 'assignedSale']);

        if ($role === 'Courier') {
            $recentLeadQuery
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('status')
                    ->orWhereNotIn('status', self::LEAD_CLOSED_STATUSES))
                ->oldest('updated_at');
            $recentApplicationQuery
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('status')
                    ->orWhereNotIn('status', self::APPLICATION_CLOSED_STATUSES))
                ->oldest('updated_at');
        } else {
            $recentLeadQuery->latest();
            $recentApplicationQuery->latest();
        }

        return [
            'viewer' => [
                'name' => $user->name ?: 'bạn',
                'role' => $role,
                'context' => $this->viewerContext($user),
            ],
            'profile' => $dashboard['profile'],
            'period' => $period,
            'periodLabel' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
            'metrics' => $dashboard['metrics'],
            'trend' => $trend,
            'trendMax' => max(1, (int) collect($trend)->max(fn (array $item): int => max($item['leads'], $item['applications']))),
            'overview' => $dashboard['overview'],
            'recentLeads' => $recentLeadQuery
                ->limit(6)
                ->get()
                ->map(fn ($lead): array => [
                    'url' => LeadResource::getUrl('view', ['record' => $lead]),
                    'code' => $lead->lead_code ?: 'Lead #'.$lead->getKey(),
                    'name' => $lead->lead_name ?: '-',
                    'project' => $lead->salesProject?->name ?: 'Chưa có dự án',
                    'owner' => $lead->assignedSale?->name ?: 'Chưa phân công',
                    'status' => $lead->status ?: 'Chưa cập nhật',
                    'time' => $lead->created_at?->format('H:i d/m/Y'),
                ])
                ->all(),
            'recentApplications' => $recentApplicationQuery
                ->limit(6)
                ->get()
                ->map(fn (Application $application): array => [
                    'url' => $this->applicationUrl($application),
                    'code' => $application->application_code ?: 'APP #'.$application->getKey(),
                    'name' => $application->customer_name ?: '-',
                    'project' => $application->salesProject?->name ?: 'Chưa có dự án',
                    'owner' => $application->assignedSale?->name ?: 'Chưa phân công',
                    'status' => $this->applicationStatusLabel($application),
                    'time' => $application->updated_at?->format('H:i d/m/Y'),
                ])
                ->all(),
            'projects' => $this->projectPerformance($leadQuery, $applicationQuery),
            'links' => [
                'leads' => LeadResource::canViewAny() ? LeadResource::getUrl('index') : null,
                'applications' => $applicationResource ? $applicationResource::getUrl('index') : null,
                'reports' => ProjectReportResource::canViewAny() ? ProjectReportResource::getUrl('index') : null,
            ],
        ];
    }

    private function visibleApplicationQuery(): Builder
    {
        $user = Auth::user();
        $query = Application::query()->with(['salesProject', 'assignedSale']);

        if (! $user?->hasAnyRole(['Admin', 'Sales Admin'])) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);

            if ($slugs === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereHas('salesProject', fn (Builder $query): Builder => $query->whereIn('slug', $slugs));
        }

        return RecordVisibility::applyUserScope($query, $user, 'assigned_sale_id', 'assignedSale');
    }

    private function trendSeries(Builder $leadQuery, Builder $applicationQuery, CarbonImmutable $start, CarbonImmutable $end, int $period): array
    {
        $leadCounts = $this->dailyCounts($leadQuery, $start, $end);
        $applicationCounts = $this->dailyCounts($applicationQuery, $start, $end);
        $bucketDays = $period <= 14 ? 1 : ($period <= 45 ? 3 : 7);
        $series = [];

        for ($cursor = $start->startOfDay(); $cursor->lte($end); $cursor = $cursor->addDays($bucketDays)) {
            $bucketEnd = $cursor->addDays($bucketDays - 1)->min($end);
            $leadTotal = 0;
            $applicationTotal = 0;

            for ($day = $cursor; $day->lte($bucketEnd); $day = $day->addDay()) {
                $key = $day->format('Y-m-d');
                $leadTotal += $leadCounts[$key] ?? 0;
                $applicationTotal += $applicationCounts[$key] ?? 0;
            }

            $series[] = [
                'label' => $bucketDays === 1 ? $cursor->format('d/m') : $cursor->format('d/m').'–'.$bucketEnd->format('d/m'),
                'leads' => $leadTotal,
                'applications' => $applicationTotal,
            ];
        }

        return $series;
    }

    private function dailyCounts(Builder $query, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return (clone $query)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS aggregate')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->mapWithKeys(fn ($row): array => [(string) $row->day => (int) $row->aggregate])
            ->all();
    }

    private function projectPerformance(Builder $leadQuery, Builder $applicationQuery): array
    {
        $user = Auth::user();
        $projectQuery = SalesProject::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name');

        if (! $user?->hasAnyRole(['Admin', 'Sales Admin'])) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);
            $projectQuery->whereIn('slug', $slugs ?: ['__none__']);
        }

        $leadCounts = (clone $leadQuery)
            ->selectRaw('sales_project_id, COUNT(*) AS aggregate')
            ->groupBy('sales_project_id')
            ->pluck('aggregate', 'sales_project_id');
        $qualifiedCounts = (clone $leadQuery)
            ->whereIn('status', ['Khách hàng thoả mãn điều kiện', 'Đã chuyển Application'])
            ->selectRaw('sales_project_id, COUNT(*) AS aggregate')
            ->groupBy('sales_project_id')
            ->pluck('aggregate', 'sales_project_id');
        $applicationCounts = (clone $applicationQuery)
            ->selectRaw('sales_project_id, COUNT(*) AS aggregate')
            ->groupBy('sales_project_id')
            ->pluck('aggregate', 'sales_project_id');

        return $projectQuery
            ->limit(10)
            ->get()
            ->map(function (SalesProject $project) use ($leadCounts, $qualifiedCounts, $applicationCounts): array {
                $leads = (int) ($leadCounts[$project->getKey()] ?? 0);
                $qualified = (int) ($qualifiedCounts[$project->getKey()] ?? 0);

                return [
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'leads' => $leads,
                    'qualified' => $qualified,
                    'applications' => (int) ($applicationCounts[$project->getKey()] ?? 0),
                    'rate' => $this->percent($qualified, $leads).'%',
                ];
            })
            ->values()
            ->all();
    }

    private function queueStats(
        Builder $leadQuery,
        Builder $applicationQuery,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $now = CarbonImmutable::now();
        $urgentFrom = $now->subDay();
        $urgentUntil = $now->subHours(4);
        $forgottenUntil = $now->subDay();
        $pendingLeadStatuses = ['new', 'pending', 'Mới', 'Lead mới', 'Chờ xử lý', 'Đang chờ xử lý'];
        $pendingApplicationStatuses = ['new', 'pending', AclMixWorkflow::PENDING_INITIAL_REVIEW, LotteFinanceWorkflow::PRE_CHECK];
        $successfulLeadStatuses = ['Khách hàng thoả mãn điều kiện', 'Đã chuyển Application', 'converted', 'completed'];
        $successfulApplicationStatuses = [
            AclMixWorkflow::COMPLETED,
            LotteFinanceWorkflow::DISBURSED,
            'completed',
            'Hoàn thành',
            'post_approval',
        ];

        $openLeads = (clone $leadQuery)->where(fn (Builder $query): Builder => $query
            ->whereNull('status')
            ->orWhereNotIn('status', self::LEAD_CLOSED_STATUSES));
        $openApplications = (clone $applicationQuery)->where(fn (Builder $query): Builder => $query
            ->whereNull('status')
            ->orWhereNotIn('status', self::APPLICATION_CLOSED_STATUSES));

        $urgentLeadCount = (clone $openLeads)->whereBetween('updated_at', [$urgentFrom, $urgentUntil])->count();
        $urgentApplicationCount = (clone $openApplications)->whereBetween('updated_at', [$urgentFrom, $urgentUntil])->count();
        $forgottenLeadCount = (clone $openLeads)->where('updated_at', '<=', $forgottenUntil)->count();
        $forgottenApplicationCount = (clone $openApplications)->where('updated_at', '<=', $forgottenUntil)->count();
        $unprocessedLeadCount = (clone $openLeads)
            ->where(fn (Builder $query): Builder => $query->whereNull('status')->orWhereIn('status', $pendingLeadStatuses))
            ->count();
        $unprocessedApplicationCount = (clone $openApplications)
            ->where(fn (Builder $query): Builder => $query->whereNull('status')->orWhereIn('status', $pendingApplicationStatuses))
            ->count();
        $processingLeadCount = (clone $openLeads)
            ->whereNotNull('status')
            ->whereNotIn('status', $pendingLeadStatuses)
            ->count();
        $processingApplicationCount = (clone $openApplications)
            ->whereNotNull('status')
            ->whereNotIn('status', $pendingApplicationStatuses)
            ->count();
        $successfulLeadCount = (clone $leadQuery)
            ->whereBetween('updated_at', [$start, $end])
            ->whereIn('status', $successfulLeadStatuses)
            ->count();
        $successfulApplicationCount = (clone $applicationQuery)
            ->whereBetween('updated_at', [$start, $end])
            ->whereIn('status', $successfulApplicationStatuses)
            ->count();

        return [
            'courierUrgentCount' => $urgentLeadCount + $urgentApplicationCount,
            'courierUrgentLeadCount' => $urgentLeadCount,
            'courierUrgentApplicationCount' => $urgentApplicationCount,
            'courierForgottenCount' => $forgottenLeadCount + $forgottenApplicationCount,
            'courierForgottenLeadCount' => $forgottenLeadCount,
            'courierForgottenApplicationCount' => $forgottenApplicationCount,
            'courierUnprocessedCount' => $unprocessedLeadCount + $unprocessedApplicationCount,
            'courierUnprocessedLeadCount' => $unprocessedLeadCount,
            'courierUnprocessedApplicationCount' => $unprocessedApplicationCount,
            'courierProcessingCount' => $processingLeadCount + $processingApplicationCount,
            'courierProcessingLeadCount' => $processingLeadCount,
            'courierProcessingApplicationCount' => $processingApplicationCount,
            'courierSuccessCount' => $successfulLeadCount + $successfulApplicationCount,
            'courierSuccessLeadCount' => $successfulLeadCount,
            'courierSuccessApplicationCount' => $successfulApplicationCount,
        ];
    }

    private function dashboardConfiguration(string $role, array $stats): array
    {
        $profile = [
            'eyebrow' => 'Performance Center',
            'title' => 'Hiệu suất công việc của tôi',
            'subtitle' => 'Số liệu theo quyền truy cập và phạm vi phụ trách',
            'trendTitle' => 'Xu hướng công việc',
            'trendDescription' => 'Lead và Application mới trong phạm vi của bạn',
            'overviewTitle' => 'Việc cần theo dõi',
            'overviewDescription' => 'Tình trạng công việc hiện tại',
            'leadTitle' => 'Lead của tôi',
            'leadDescription' => 'Lead gần đây trong phạm vi được xem',
            'applicationTitle' => 'Hồ sơ của tôi',
            'applicationDescription' => 'Tiến độ xử lý gần đây',
            'projectTitle' => 'Hiệu suất theo dự án của tôi',
            'projectDescription' => 'Chỉ hiển thị các dự án được phép truy cập',
        ];

        $metrics = [
            $this->metric(
                'Lead của tôi trong kỳ',
                $stats['leadCount'],
                $this->comparisonText($stats['leadCount'], $stats['previousLeadCount']),
                $stats['leadCount'] <=> $stats['previousLeadCount'],
                'heroicon-o-user-plus',
                'blue',
            ),
            $this->metric(
                'Tỷ lệ đạt sơ bộ',
                $this->percent($stats['qualifiedLeadCount'], $stats['leadCount']).'%',
                number_format($stats['qualifiedLeadCount']).' đạt · '.number_format($stats['rejectedLeadCount']).' từ chối/trùng',
                0,
                'heroicon-o-check-circle',
                'green',
            ),
            $this->metric(
                'Cần Sale bổ sung',
                $stats['saleActionRequiredCount'],
                'Hồ sơ chờ hoàn thiện hoặc được trả về',
                0,
                'heroicon-o-pencil-square',
                'amber',
            ),
            $this->metric(
                'Application đang xử lý',
                $stats['activeApplicationCount'],
                number_format($stats['applicationCount']).' hồ sơ mới trong kỳ',
                $stats['applicationCount'] <=> $stats['previousApplicationCount'],
                'heroicon-o-briefcase',
                'violet',
            ),
        ];

        $overview = [
            ['label' => 'Lead đạt sơ bộ', 'value' => $stats['qualifiedLeadCount'], 'tone' => 'green'],
            ['label' => 'Lead từ chối hoặc trùng', 'value' => $stats['rejectedLeadCount'], 'tone' => 'red'],
            ['label' => 'Hồ sơ cần Sale bổ sung', 'value' => $stats['saleActionRequiredCount'], 'tone' => 'amber'],
            ['label' => 'Application đang xử lý', 'value' => $stats['activeApplicationCount'], 'tone' => 'violet'],
        ];

        if ($role === 'Admin') {
            $profile = array_merge($profile, [
                'eyebrow' => 'Trung tâm điều hành',
                'title' => 'Tổng quan toàn hệ thống',
                'subtitle' => 'Toàn bộ hoạt động kinh doanh và vận hành CRM',
                'trendTitle' => 'Xu hướng toàn hệ thống',
                'trendDescription' => 'Lead và Application mới phát sinh',
                'overviewTitle' => 'Tình trạng vận hành',
                'overviewDescription' => 'Các hạng mục cần Admin theo dõi',
                'leadTitle' => 'Lead mới toàn hệ thống',
                'leadDescription' => 'Hoạt động cập nhật gần nhất',
                'applicationTitle' => 'Application mới cập nhật',
                'applicationDescription' => 'Tiến độ xử lý toàn hệ thống',
                'projectTitle' => 'Hiệu suất theo dự án',
                'projectDescription' => 'Số liệu tích lũy trên các dự án đang hoạt động',
            ]);
            $metrics = [
                $this->metric('Lead toàn hệ thống', $stats['leadCount'], $this->comparisonText($stats['leadCount'], $stats['previousLeadCount']), $stats['leadCount'] <=> $stats['previousLeadCount'], 'heroicon-o-user-plus', 'blue'),
                $this->metric('Tỷ lệ đạt sơ bộ', $this->percent($stats['qualifiedLeadCount'], $stats['leadCount']).'%', number_format($stats['qualifiedLeadCount']).' đạt · '.number_format($stats['rejectedLeadCount']).' từ chối/trùng', 0, 'heroicon-o-check-circle', 'green'),
                $this->metric('Application đang xử lý', $stats['activeApplicationCount'], $this->comparisonText($stats['applicationCount'], $stats['previousApplicationCount']).' · '.number_format($stats['applicationCount']).' mới', $stats['applicationCount'] <=> $stats['previousApplicationCount'], 'heroicon-o-briefcase', 'amber'),
                $this->metric('Báo cáo chờ xử lý', $stats['pendingReportCount'], number_format($stats['reportCount']).' báo cáo mới trong kỳ', 0, 'heroicon-o-chart-bar-square', 'violet'),
            ];
            $overview = [
                ['label' => 'Lead chưa phân công', 'value' => $stats['unassignedLeadCount'], 'tone' => 'amber'],
                ['label' => 'Hồ sơ chờ kiểm tra', 'value' => $stats['reviewQueueCount'], 'tone' => 'violet'],
                ['label' => 'Application đang xử lý', 'value' => $stats['activeApplicationCount'], 'tone' => 'green'],
                ['label' => 'Báo cáo chờ quyết định', 'value' => $stats['pendingReportCount'], 'tone' => 'red'],
            ];
        } elseif (in_array($role, ['ZD', 'AM', 'Team Leader'], true)) {
            $titles = [
                'ZD' => 'Hiệu suất khu vực phụ trách',
                'AM' => 'Hiệu suất cụm kinh doanh',
                'Team Leader' => 'Hiệu suất đội bán hàng',
            ];
            $profile = array_merge($profile, [
                'eyebrow' => 'Quản trị kinh doanh',
                'title' => $titles[$role],
                'subtitle' => 'Số liệu đã giới hạn theo line quản lý của bạn',
                'trendTitle' => 'Xu hướng trong nhánh',
                'trendDescription' => 'Lead và Application của đội ngũ phụ trách',
                'overviewTitle' => 'Điểm cần điều hành',
                'overviewDescription' => 'Phân công, xử lý và chất lượng Lead',
                'leadTitle' => 'Lead trong nhánh',
                'leadDescription' => 'Lead gần đây của đội ngũ phụ trách',
                'applicationTitle' => 'Application trong nhánh',
                'applicationDescription' => 'Tiến độ hồ sơ của đội ngũ',
                'projectTitle' => 'Hiệu suất dự án của nhánh',
                'projectDescription' => 'Dự án được cấp cho line quản lý của bạn',
            ]);
            $metrics = [
                $this->metric('Lead đã upload của team', $stats['leadCount'], $this->comparisonText($stats['leadCount'], $stats['previousLeadCount']), $stats['leadCount'] <=> $stats['previousLeadCount'], 'heroicon-o-arrow-up-tray', 'blue'),
                $this->metric('Application đã upload của team', $stats['applicationCount'], $this->comparisonText($stats['applicationCount'], $stats['previousApplicationCount']), $stats['applicationCount'] <=> $stats['previousApplicationCount'], 'heroicon-o-document-arrow-up', 'violet'),
                $this->metric('Lead thành công của team', $stats['qualifiedLeadCount'], $this->percent($stats['qualifiedLeadCount'], $stats['leadCount']).'% Lead đạt trong kỳ', 0, 'heroicon-o-check-circle', 'green'),
                $this->metric('Application thành công của team', $stats['completedApplicationCount'], 'Hoàn tất trong khoảng thời gian đã chọn', 0, 'heroicon-o-check-badge', 'amber'),
            ];
            $overview = [
                ['label' => 'Lead đã upload', 'value' => $stats['leadCount'], 'tone' => 'blue'],
                ['label' => 'Application đã upload', 'value' => $stats['applicationCount'], 'tone' => 'violet'],
                ['label' => 'Lead thành công', 'value' => $stats['qualifiedLeadCount'], 'tone' => 'green'],
                ['label' => 'Application thành công', 'value' => $stats['completedApplicationCount'], 'tone' => 'amber'],
            ];
        } elseif ($role === 'Courier Manager') {
            $profile = array_merge($profile, [
                'eyebrow' => 'Điều hành Courier',
                'title' => 'Hiệu suất đội xử lý hồ sơ',
                'subtitle' => 'Chỉ hiển thị hồ sơ và Courier thuộc phạm vi quản lý',
                'trendTitle' => 'Xu hướng hồ sơ trong đội',
                'trendDescription' => 'Application mới và lượng Lead liên quan',
                'overviewTitle' => 'Hàng đợi xử lý',
                'overviewDescription' => 'Các trạng thái Courier cần theo dõi',
                'leadTitle' => 'Lead liên quan',
                'leadDescription' => 'Lead trong phạm vi đội Courier',
                'applicationTitle' => 'Hồ sơ đội Courier',
                'applicationDescription' => 'Hồ sơ mới cập nhật trong đội',
                'projectTitle' => 'Hiệu suất dự án của đội Courier',
                'projectDescription' => 'Dự án có hồ sơ được phân cho đội',
            ]);
            $metrics = [
                $this->metric('Hồ sơ mới trong đội', $stats['applicationCount'], $this->comparisonText($stats['applicationCount'], $stats['previousApplicationCount']), $stats['applicationCount'] <=> $stats['previousApplicationCount'], 'heroicon-o-inbox-stack', 'blue'),
                $this->metric('Courier đang hoạt động', max(0, $stats['activeMemberCount'] - 1), 'Nhân sự thuộc phạm vi quản lý', 0, 'heroicon-o-users', 'green'),
                $this->metric('Hồ sơ chờ kiểm tra', $stats['reviewQueueCount'], 'Cần phân công hoặc cập nhật quyết định', 0, 'heroicon-o-clock', 'amber'),
                $this->metric('Hoàn thành trong kỳ', $stats['completedApplicationCount'], 'Hồ sơ đã đóng quy trình xử lý', 0, 'heroicon-o-check-badge', 'violet'),
            ];
            $overview = [
                ['label' => 'Hồ sơ chờ kiểm tra', 'value' => $stats['reviewQueueCount'], 'tone' => 'amber'],
                ['label' => 'Hồ sơ đang xử lý', 'value' => $stats['activeApplicationCount'], 'tone' => 'violet'],
                ['label' => 'Hồ sơ trả về Sale', 'value' => $stats['saleActionRequiredCount'], 'tone' => 'red'],
                ['label' => 'Hoàn thành trong kỳ', 'value' => $stats['completedApplicationCount'], 'tone' => 'green'],
            ];
        } elseif ($role === 'Courier') {
            $profile = array_merge($profile, [
                'eyebrow' => 'Courier Workspace',
                'title' => 'Hàng đợi xử lý của tôi',
                'subtitle' => 'Hồ sơ được phân trực tiếp và các bước cần hoàn tất',
                'trendTitle' => 'Khối lượng xử lý của tôi',
                'trendDescription' => 'Application mới trong khoảng thời gian đã chọn',
                'overviewTitle' => 'Cần xử lý',
                'overviewDescription' => 'Hàng đợi được phân loại theo trạng thái và thời gian',
                'leadTitle' => 'Lead cần xử lý',
                'leadDescription' => 'Ưu tiên dữ liệu tồn đọng lâu nhất',
                'applicationTitle' => 'Application cần xử lý',
                'applicationDescription' => 'Ưu tiên hồ sơ tồn đọng lâu nhất',
                'projectTitle' => 'Khối lượng theo dự án',
                'projectDescription' => 'Hồ sơ được giao theo từng dự án',
            ]);
            $metrics = [
                $this->metric('Cần xử lý gấp', $stats['courierUrgentCount'], number_format($stats['courierUrgentLeadCount']).' Lead · '.number_format($stats['courierUrgentApplicationCount']).' Application', 0, 'heroicon-o-bolt', 'red'),
                $this->metric('Bỏ quên', $stats['courierForgottenCount'], number_format($stats['courierForgottenLeadCount']).' Lead · '.number_format($stats['courierForgottenApplicationCount']).' Application', 0, 'heroicon-o-exclamation-triangle', 'amber'),
                $this->metric('Chưa xử lý', $stats['courierUnprocessedCount'], number_format($stats['courierUnprocessedLeadCount']).' Lead · '.number_format($stats['courierUnprocessedApplicationCount']).' Application', 0, 'heroicon-o-inbox-stack', 'violet'),
                $this->metric('Đang xử lý', $stats['courierProcessingCount'], number_format($stats['courierProcessingLeadCount']).' Lead · '.number_format($stats['courierProcessingApplicationCount']).' Application', 0, 'heroicon-o-arrow-path', 'blue'),
                $this->metric('Đã xử lý thành công', $stats['courierSuccessCount'], number_format($stats['courierSuccessLeadCount']).' Lead · '.number_format($stats['courierSuccessApplicationCount']).' Application trong kỳ', 0, 'heroicon-o-check-badge', 'green'),
            ];
            $overview = [
                ['label' => 'Cần xử lý gấp', 'value' => $stats['courierUrgentCount'], 'tone' => 'red'],
                ['label' => 'Bỏ quên', 'value' => $stats['courierForgottenCount'], 'tone' => 'amber'],
                ['label' => 'Chưa xử lý', 'value' => $stats['courierUnprocessedCount'], 'tone' => 'violet'],
                ['label' => 'Đang xử lý', 'value' => $stats['courierProcessingCount'], 'tone' => 'blue'],
                ['label' => 'Đã xử lý thành công', 'value' => $stats['courierSuccessCount'], 'tone' => 'green'],
            ];
        }

        return compact('profile', 'metrics', 'overview');
    }

    private function viewerContext(User $user): string
    {
        $department = UserSpecOptions::labelFor('department', $user->department);

        return collect([
            $department !== '-' ? $department : null,
            $user->company_name,
            $user->branch_name,
        ])->filter(fn ($value): bool => filled($value))
            ->unique()
            ->implode(' · ') ?: 'Phạm vi cá nhân';
    }

    private function metric(string $label, int|string $value, string $meta, int $direction, string $icon, string $tone): array
    {
        return compact('label', 'value', 'meta', 'direction', 'icon', 'tone');
    }

    private function applicationUrl(Application $application): string
    {
        return match ($application->salesProject?->slug) {
            'cbp' => CbpApplicationResource::getUrl('view', ['record' => $application]),
            'lotte-finance' => LotteFinanceApplicationResource::getUrl('view', ['record' => $application]),
            default => ApplicationResource::getUrl('view', ['record' => $application]),
        };
    }

    private function applicationStatusLabel(Application $application): string
    {
        return match ($application->salesProject?->slug) {
            'lotte-finance' => LotteFinanceWorkflow::statusLabel($application->status),
            'acl-mix' => AclMixWorkflow::statusLabel($application->status),
            default => $application->status ?: 'Chưa cập nhật',
        };
    }

    private function comparisonText(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current === 0 ? 'Không đổi so với kỳ trước' : 'Mới phát sinh trong kỳ';
        }

        $change = round((($current - $previous) / $previous) * 100, 1);
        $formatted = rtrim(rtrim(number_format(abs($change), 1, '.', ''), '0'), '.');

        return ($change >= 0 ? 'Tăng ' : 'Giảm ').$formatted.'% so với kỳ trước';
    }

    private function percent(int $value, int $total): string
    {
        if ($total <= 0) {
            return '0';
        }

        $percent = round(($value / $total) * 100, 1);

        return rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.');
    }
}
