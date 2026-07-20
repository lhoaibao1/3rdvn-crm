<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Events\SaleProfileApproved;
use App\Events\SaleProfileRejected;
use App\Models\ApprovalLog;
use App\Models\SaleProfile;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasAnyRole(['Admin', 'Manager']), 403);
        return view('modules.approvals.index', [
            'profiles' => SaleProfile::where('approval_status', 'Chờ duyệt')->latest()->paginate(20),
        ]);
    }

    public function approve(SaleProfile $profile)
    {
        abort_unless(auth()->user()->hasAnyRole(['Admin', 'Manager']), 403);
        abort_unless($profile->approval_status === 'Chờ duyệt', 422);

        $profile->update([
            'status' => 'Đã duyệt',
            'approval_status' => 'Đã duyệt',
            'approved_by_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        ApprovalLog::create([
            'sale_profile_id' => $profile->id,
            'action' => 'Approved',
            'actor_id' => auth()->id(),
            'action_at' => now(),
            'new_status' => 'Đã duyệt',
        ]);

        SaleProfileApproved::dispatch($profile);

        return back()->with('success', 'Đã duyệt hồ sơ.');
    }

    public function reject(Request $request, SaleProfile $profile)
    {
        abort_unless(auth()->user()->hasAnyRole(['Admin', 'Manager']), 403);
        abort_unless($profile->approval_status === 'Chờ duyệt', 422);

        $data = $request->validate(['rejection_reason' => ['required', 'string']]);

        $profile->update([
            'status' => 'Từ chối',
            'approval_status' => 'Từ chối',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        ApprovalLog::create([
            'sale_profile_id' => $profile->id,
            'action' => 'Rejected',
            'actor_id' => auth()->id(),
            'action_at' => now(),
            'new_status' => 'Từ chối',
            'reason' => $data['rejection_reason'],
        ]);

        SaleProfileRejected::dispatch($profile);

        return back()->with('success', 'Đã từ chối hồ sơ.');
    }
}
