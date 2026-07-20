<?php

namespace App\Http\Controllers\Crm;

use App\Events\LeadConverted;
use App\Events\LeadCreated;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SaleProfile;
use App\Support\Permissions\LeadAccess;
use App\Support\SalesLineSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Lead::class);

        $query = LeadAccess::applyVisibleTo(Lead::query()->with('salesProject'), auth()->user());

        return view('modules.leads.index', ['leads' => $query->latest()->paginate(20)]);
    }

    public function create()
    {
        $this->authorize('create', Lead::class);

        return view('modules.leads.form', [
            'lead' => new Lead(),
            'salesProjects' => LeadAccess::projectOptions(auth()->user()),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Lead::class);

        $data = $request->validate([
            'sales_project_id' => ['nullable', 'integer', 'exists:sales_projects,id'],
            'lead_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ]);

        $data['sales_project_id'] = LeadAccess::normalizeProjectId(auth()->user(), $data['sales_project_id'] ?? null);
        abort_unless(LeadAccess::canUseProjectId(auth()->user(), $data['sales_project_id']), 403);

        $data = array_replace($data, SalesLineSnapshot::fromUser(auth()->user()));
        $data['status'] = 'Mới';

        $lead = Lead::create($data);
        LeadCreated::dispatch($lead);

        return redirect()->route('leads.index')->with('success', 'Đã tạo lead.');
    }

    public function edit(Lead $lead)
    {
        $this->authorize('update', $lead);

        return view('modules.leads.form', [
            'lead' => $lead,
            'salesProjects' => LeadAccess::projectOptions(auth()->user()),
        ]);
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);

        return view('modules.leads.show', compact('lead'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        abort_if($lead->converted_sale_profile_id, 403, 'Lead đã chuyển hồ sơ.');

        $data = $request->validate([
            'sales_project_id' => ['nullable', 'integer', 'exists:sales_projects,id'],
            'lead_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ]);

        $data['sales_project_id'] = LeadAccess::normalizeProjectId(auth()->user(), $data['sales_project_id'] ?? null);
        abort_unless(LeadAccess::canUseProjectId(auth()->user(), $data['sales_project_id']), 403);

        $lead->update($data);

        return redirect()->route('leads.index')->with('success', 'Đã cập nhật lead.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        abort_if($lead->converted_sale_profile_id, 403, 'Không thể xóa lead đã chuyển hồ sơ.');

        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Đã xóa lead.');
    }

    public function convert(Lead $lead)
    {
        $this->authorize('update', $lead);
        abort_if($lead->converted_sale_profile_id, 422, 'Lead đã được chuyển hồ sơ.');

        DB::transaction(function () use ($lead): void {
            $profile = SaleProfile::create([
                'customer_name' => $lead->lead_name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'sale_owner_id' => auth()->id(),
                'source_lead_id' => $lead->id,
                'status' => 'Nháp',
                'approval_status' => 'Chưa gửi',
            ]);

            $lead->update([
                'status' => 'Đã chuyển hồ sơ',
                'converted_sale_profile_id' => $profile->id,
                'converted_at' => now(),
                'converted_by_id' => auth()->id(),
            ]);
        });

        LeadConverted::dispatch($lead->refresh());

        return redirect()->route('profiles.index')->with('success', 'Đã chuyển lead thành hồ sơ.');
    }
}
