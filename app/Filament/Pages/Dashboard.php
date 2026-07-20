<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\SaleProfiles\SaleProfileResource;
use App\Models\Application;
use App\Models\SalesProject;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use BackedEnum;
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

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.dashboard';

    protected Width | string | null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return (bool) ($user?->hasRole('Admin') || $user?->can('dashboard.view'));
    }

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    protected function getViewData(): array
    {
        $leadQuery = LeadResource::getEloquentQuery();
        $profileQuery = SaleProfileResource::getEloquentQuery();
        $applicationQuery = $this->visibleApplicationQuery();

        $totalLeads = (clone $leadQuery)->count();
        $qualifiedLeads = (clone $leadQuery)->whereIn('status', ['Khách hàng thoả mãn điều kiện', 'Đã chuyển Application'])->count();
        $rejectedLeads = (clone $leadQuery)->whereIn('status', ['Từ chối', 'Khách hàng bị trùng'])->count();
        $todayLeads = (clone $leadQuery)->whereDate('created_at', today())->count();

        $totalProfiles = (clone $profileQuery)->count();
        $processingProfiles = (clone $profileQuery)
            ->where(fn (Builder $query): Builder => $query
                ->whereIn('processing_status', ['pending', 'processing'])
                ->orWhereNull('processing_status'))
            ->count();
        $completedProfiles = (clone $profileQuery)->whereIn('status', ['completed', 'Hoàn tất'])->count();
        $rejectedProfiles = (clone $profileQuery)->whereIn('status', ['rejected', 'Từ chối'])->count();

        $totalApplications = (clone $applicationQuery)->count();
        $todayApplications = (clone $applicationQuery)->whereDate('created_at', today())->count();

        return [
            'kpis' => [
                [
                    'label' => 'Lead hôm nay',
                    'value' => $todayLeads,
                    'meta' => 'Tổng lead: '.number_format($totalLeads),
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Tỷ lệ đạt',
                    'value' => $this->percent($qualifiedLeads, $totalLeads).'%',
                    'meta' => number_format($qualifiedLeads).' lead thỏa điều kiện',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Hồ sơ đang xử lý',
                    'value' => $processingProfiles,
                    'meta' => 'Hoàn tất: '.number_format($completedProfiles),
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Application hôm nay',
                    'value' => $todayApplications,
                    'meta' => 'Tổng application: '.number_format($totalApplications),
                    'tone' => 'violet',
                ],
            ],
            'pipeline' => [
                ['label' => 'Lead', 'value' => $totalLeads, 'tone' => 'blue'],
                ['label' => 'Thỏa điều kiện', 'value' => $qualifiedLeads, 'tone' => 'green'],
                ['label' => 'Application', 'value' => $totalApplications, 'tone' => 'violet'],
                ['label' => 'Hồ sơ hoàn tất', 'value' => $completedProfiles, 'tone' => 'slate'],
            ],
            'health' => [
                ['label' => 'Lead bị từ chối/trùng', 'value' => $rejectedLeads, 'tone' => 'red'],
                ['label' => 'Hồ sơ từ chối', 'value' => $rejectedProfiles, 'tone' => 'red'],
                ['label' => 'Tỷ lệ hoàn tất hồ sơ', 'value' => $this->percent($completedProfiles, $totalProfiles).'%', 'tone' => 'green'],
            ],
            'recentLeads' => (clone $leadQuery)
                ->with(['salesProject', 'assignedSale'])
                ->latest()
                ->limit(6)
                ->get(),
            'processingQueue' => (clone $profileQuery)
                ->with(['saleOwner', 'processingOwner', 'sourceLead'])
                ->where(fn (Builder $query): Builder => $query
                    ->whereIn('processing_status', ['pending', 'processing'])
                    ->orWhereNull('processing_status'))
                ->latest()
                ->limit(6)
                ->get(),
            'projects' => $this->projectPerformance($leadQuery),
            'links' => [
                'leads' => url('/leads'),
                'profiles' => url('/sale-profiles'),
                'applications' => url('/applications/acl-mix'),
            ],
        ];
    }

    private function visibleApplicationQuery(): Builder
    {
        $user = Auth::user();
        $query = Application::query()->with(['salesProject', 'assignedSale']);

        if (! $user?->hasRole('Admin')) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);

            if ($slugs === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereHas('salesProject', fn (Builder $query): Builder => $query->whereIn('slug', $slugs));
        }

        return RecordVisibility::applyUserScope($query, $user, 'assigned_sale_id', 'assignedSale');
    }

    private function projectPerformance(Builder $leadQuery): array
    {
        $user = Auth::user();
        $projectQuery = SalesProject::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name');

        if (! $user?->hasRole('Admin')) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);
            $projectQuery->whereIn('slug', $slugs ?: ['__none__']);
        }

        $leadCounts = (clone $leadQuery)
            ->selectRaw('sales_project_id, count(*) as total')
            ->groupBy('sales_project_id')
            ->pluck('total', 'sales_project_id');

        return $projectQuery
            ->limit(6)
            ->get()
            ->map(fn (SalesProject $project): array => [
                'name' => $project->name,
                'slug' => $project->slug,
                'count' => (int) ($leadCounts[$project->getKey()] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function percent(int $value, int $total): string
    {
        if ($total <= 0) {
            return '0';
        }

        $percent = round(($value / $total) * 100, 1);

        return rtrim(rtrim((string) $percent, '0'), '.');
    }
}
