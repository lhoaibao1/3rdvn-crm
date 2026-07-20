<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\ApiMapping;
use App\Models\Lead;
use App\Models\SaleProfile;

class DashboardController extends Controller
{
    public function index()
    {
        $query = SaleProfile::query();

        if (auth()->user()->hasRole('Sale')) {
            $query->where('sale_owner_id', auth()->id());
        }

        $profiles = $query->latest()->paginate(15);
        $statsQuery = clone $query;

        return view('dashboard.index', [
            'profiles' => $profiles,
            'totalProfiles' => (clone $statsQuery)->count(),
            'newLeads' => Lead::query()->where('status', 'Mới')->count(),
            'approvedProfiles' => (clone $statsQuery)->where('approval_status', 'Đã duyệt')->count(),
            'pendingProfiles' => (clone $statsQuery)->whereIn('approval_status', ['Chờ duyệt', 'Đang duyệt'])->count(),
            'activeMappings' => ApiMapping::query()->where('is_active', true)->count(),
            'recentLeads' => Lead::query()->latest()->limit(5)->get(),
            'approvalQueue' => SaleProfile::query()->where('approval_status', 'Chờ duyệt')->latest()->limit(5)->get(),
        ]);
    }
}
