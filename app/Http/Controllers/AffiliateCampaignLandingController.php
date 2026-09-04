<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCampaign;
use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AffiliateCampaignLandingController extends Controller
{
    /**
     * Display the Proxy Landing Page or direct redirect if requested.
     */
    public function show(Request $request, AffiliateCampaign $campaign): View|RedirectResponse
    {
        abort_unless($campaign->is_active, 404);
        
        $employeeCode = trim((string) $request->query('ref'));
        abort_if($employeeCode === '', 404);

        $salesUser = User::query()
            ->where('employee_code', $employeeCode)
            ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
            ->first();
            
        abort_unless($salesUser, 404);

        if ($request->boolean('direct')) {
            return $this->buildRedirect($campaign, $salesUser);
        }

        return view('affiliate.landing', [
            'campaign' => $campaign,
            'salesUser' => $salesUser,
            'employeeCode' => $employeeCode,
            'submitUrl' => route('affiliate.landing.submit', ['campaign' => $campaign->slug, 'ref' => $employeeCode]),
        ]);
    }

    /**
     * Handle Customer Submission, create Lead in CRM, and redirect to partner page with full prefill.
     */
    public function submit(Request $request, AffiliateCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->is_active, 404);

        $employeeCode = trim((string) $request->input('ref'));
        $salesUser = User::query()
            ->where('employee_code', $employeeCode)
            ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
            ->firstOrFail();

        $validated = $request->validate([
            'applicant_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
            'identity_number' => ['required', 'string', 'regex:/^[0-9]{9,12}$/'],
            'dob' => ['nullable', 'string', 'max:50'],
            'job' => ['nullable', 'string', 'max:100'],
            'loan_amount' => ['nullable', 'string', 'max:50'],
        ], [
            'applicant_name.required' => 'Vui lòng nhập Họ và tên.',
            'phone.required' => 'Vui lòng nhập Số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng (10 số, bắt đầu bằng 0).',
            'identity_number.required' => 'Vui lòng nhập Số CCCD/CMND.',
            'identity_number.regex' => 'Số CCCD/CMND phải gồm 9 hoặc 12 chữ số.',
        ]);

        $fullName = trim($validated['applicant_name']);
        $phone = preg_replace('/\D+/', '', $validated['phone']);
        $cccd = preg_replace('/\D+/', '', $validated['identity_number']);
        $dob = trim($validated['dob'] ?? '');
        $job = trim($validated['job'] ?? '');
        $loanAmount = trim($validated['loan_amount'] ?? '10000000');

        // Find or create sales project for affiliate/SHB
        $salesProject = SalesProject::query()
            ->where('slug', 'shb-finance')
            ->orWhere('slug', $campaign->slug)
            ->first();

        // Create Lead record in CRM
        $lead = DB::transaction(function () use ($fullName, $phone, $cccd, $dob, $job, $loanAmount, $salesUser, $salesProject, $campaign): Lead {
            return Lead::create([
                'lead_name' => $fullName,
                'phone' => $phone,
                'assigned_sale_id' => $salesUser->id,
                'created_by_id' => $salesUser->id,
                'team_id' => $salesUser->team_id ?? null,
                'sales_project_id' => $salesProject?->id,
                'source' => 'affiliate_landing',
                'status' => 'Mới',
                'payload' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'employee_code' => $salesUser->employee_code,
                    'fields' => [
                        'applicant_name' => $fullName,
                        'phone' => $phone,
                        'identity_number' => $cccd,
                        'dob' => $dob ?: null,
                        'job' => $job ?: null,
                        'loan_amount' => $loanAmount ?: null,
                    ],
                ],
            ]);
        });

        // Build Partner Tracking URL with tracking params + standard prefill params
        return $this->buildRedirect($campaign, $salesUser, $lead, $fullName, $phone, $cccd, $dob, $loanAmount);
    }

    /**
     * Build the redirect URL with aff_sub tracking AND prefill fields.
     */
    private function buildRedirect(
        AffiliateCampaign $campaign,
        User $salesUser,
        ?Lead $lead = null,
        ?string $fullName = null,
        ?string $phone = null,
        ?string $cccd = null,
        ?string $dob = null,
        ?string $loanAmount = null
    ): RedirectResponse {
        $separator = str_contains($campaign->tracking_url, '?') ? '&' : '?';
        $attributionParam = trim((string) ($campaign->attribution_param ?: 'aff_sub1'));

        $params = [
            $attributionParam => $salesUser->employee_code,
        ];

        // Affiliate Tracking Sub-IDs
        if ($lead) {
            $params['aff_sub2'] = (string) $lead->id;
        }
        if ($phone) {
            $params['aff_sub3'] = $phone;
        }
        if ($cccd) {
            $params['aff_sub4'] = $cccd;
        }

        // Standard Auto-Prefill parameters
        if ($fullName) {
            $params['fullname'] = $fullName;
            $params['name'] = $fullName;
            $params['customer_name'] = $fullName;
        }
        if ($phone) {
            $params['phone'] = $phone;
            $params['mobile'] = $phone;
            $params['phoneNumber'] = $phone;
        }
        if ($cccd) {
            $params['national_id'] = $cccd;
            $params['id_card'] = $cccd;
            $params['idCard'] = $cccd;
            $params['cccd'] = $cccd;
        }
        if ($dob) {
            $params['dob'] = $dob;
            $params['birthday'] = $dob;
        }
        if ($loanAmount) {
            $params['loan_amount'] = $loanAmount;
            $params['amount'] = $loanAmount;
        }

        $queryString = http_build_query($params);

        return redirect()->away($campaign->tracking_url . $separator . $queryString);
    }
}
