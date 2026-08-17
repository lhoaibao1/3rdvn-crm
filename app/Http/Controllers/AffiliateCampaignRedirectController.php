<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCampaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AffiliateCampaignRedirectController extends Controller
{
    public function __invoke(Request $request, AffiliateCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->is_active, 404);
        $employeeCode = trim((string) $request->query('ref'));
        abort_if($employeeCode === '', 404);

        $validUser = User::query()
            ->where('employee_code', $employeeCode)
            ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
            ->exists();
        abort_unless($validUser, 404);

        $separator = str_contains($campaign->tracking_url, '?') ? '&' : '?';

        $attributionParam = trim((string) ($campaign->attribution_param ?: 'aff_sub1'));

        return redirect()->away($campaign->tracking_url.$separator.rawurlencode($attributionParam).'='.rawurlencode($employeeCode));
    }
}
