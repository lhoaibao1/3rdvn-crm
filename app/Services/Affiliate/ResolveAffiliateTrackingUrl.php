<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateCampaign;
use Illuminate\Http\Request;

class ResolveAffiliateTrackingUrl
{
    public function handle(AffiliateCampaign $campaign, Request $request): string
    {
        if (! $campaign->tracking_url_ios) {
            return (string) $campaign->tracking_url;
        }

        $platform = strtolower(trim((string) $request->input('platform', $request->query('platform', ''))));
        $isIos = in_array($platform, ['ios', 'iphone', 'ipad'], true)
            || (bool) preg_match('/iPhone|iPad|iPod/i', (string) $request->userAgent());

        return (string) ($isIos ? $campaign->tracking_url_ios : $campaign->tracking_url);
    }
}
