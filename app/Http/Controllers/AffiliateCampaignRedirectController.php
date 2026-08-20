<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            ->first();
            
        abort_unless($validUser, 404);

        // Record Click in affiliate_clicks for 100% accurate realtime traffic
        try {
            $partner = match(true) {
                str_contains(strtolower($campaign->slug . $campaign->name), 'vpbank') => 'isclix',
                str_contains(strtolower($campaign->slug . $campaign->name), 'tinvay') => 'accesstrade',
                default => 'hyperlead',
            };

            AffiliateClick::create([
                'campaign_id' => $campaign->id,
                'campaign_slug' => $campaign->slug,
                'campaign_name' => $campaign->name,
                'partner' => $partner,
                'employee_code' => $validUser->employee_code,
                'user_id' => $validUser->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'referer' => substr((string) $request->header('referer'), 0, 500),
                'clicked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log affiliate click: ' . $e->getMessage());
        }

        // Build redirect URL with all standard affiliate parameters
        $separator = str_contains($campaign->tracking_url, '?') ? '&' : '?';
        
        $params = [
            'aff_sub1' => $validUser->employee_code,
            'sub1' => $validUser->employee_code,
            'utm_content' => $validUser->employee_code,
            'utm_source' => '3rdvn',
            'utm_medium' => 'affiliate',
        ];

        return redirect()->away($campaign->tracking_url . $separator . http_build_query($params));
    }
}
