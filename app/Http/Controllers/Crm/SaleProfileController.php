<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Events\SaleProfileSubmitted;
use App\Models\ApprovalLog;
use App\Models\SaleProfile;
use Illuminate\Http\Request;

class SaleProfileController extends Controller
{
    public function index()
    {
        $query = SaleProfile::query();
        if (auth()->user()->hasRole('Sale')) $query->where('sale_owner_id', auth()->id());
        return view('modules.profiles.index', ['profiles' => $query->latest()->paginate(20)]);
    }

    public function create()
    {
        return view('modules.profiles.form', ['profile' => new SaleProfile()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProfile($request);
        $data['sale_owner_id'] = auth()->id();
        $data['status'] = 'Nháp';
        $data['approval_status'] = 'Chưa gửi';
        SaleProfile::create($data);
        return redirect()->route('profiles.index')->with('success', 'Đã tạo hồ sơ.');
    }

    public function edit(SaleProfile $profile)
    {
        $this->authorizeProfile($profile);
        return view('modules.profiles.form', compact('profile'));
    }

    public function show(SaleProfile $profile)
    {
        $this->authorizeProfile($profile);
        return view('modules.profiles.show', compact('profile'));
    }

    public function update(Request $request, SaleProfile $profile)
    {
        $this->authorizeProfile($profile);
        abort_if($profile->approval_status === 'Đã duyệt' && auth()->user()->hasRole('Sale'), 403);
        $profile->update($this->validateProfile($request));
        return redirect()->route('profiles.index')->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function destroy(SaleProfile $profile)
    {
        abort_unless(auth()->user()->hasRole('Admin'), 403);
        $profile->delete();
        return redirect()->route('profiles.index')->with('success', 'Đã xóa hồ sơ.');
    }

    public function submit(SaleProfile $profile)
    {
        $this->authorizeProfile($profile);
        abort_unless(in_array($profile->approval_status, ['Chưa gửi', 'Từ chối']), 422);

        $profile->update(['status' => 'Chờ duyệt', 'approval_status' => 'Chờ duyệt']);

        ApprovalLog::create([
            'sale_profile_id' => $profile->id,
            'action' => 'Submitted',
            'actor_id' => auth()->id(),
            'action_at' => now(),
            'new_status' => 'Chờ duyệt',
        ]);

        SaleProfileSubmitted::dispatch($profile);

        return redirect()->route('profiles.index')->with('success', 'Đã gửi duyệt.');
    }

    public function process(SaleProfile $profile)
    {
        abort_unless(auth()->user()->hasAnyRole(['Admin', 'Ops']), 403);
        abort_unless($profile->approval_status === 'Đã duyệt', 422);

        $profile->update([
            'status' => 'Đang xử lý',
            'processing_status' => 'Đang xử lý',
            'processing_owner_id' => auth()->id(),
        ]);

        return back()->with('success', 'Đã nhận xử lý hồ sơ.');
    }

    public function complete(SaleProfile $profile)
    {
        abort_unless(auth()->user()->hasAnyRole(['Admin', 'Ops']), 403);

        $profile->update([
            'status' => 'Hoàn tất',
            'processing_status' => 'Hoàn tất',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Đã hoàn tất hồ sơ.');
    }

    private function validateProfile(Request $request): array
    {
        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'product_interest' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function authorizeProfile(SaleProfile $profile): void
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['Admin', 'Manager'])) return;
        abort_unless($profile->sale_owner_id === $user->id, 403);
    }
}
