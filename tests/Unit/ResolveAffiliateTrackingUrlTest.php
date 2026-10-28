<?php

namespace Tests\Unit;

use App\Models\AffiliateCampaign;
use App\Services\Affiliate\ResolveAffiliateTrackingUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResolveAffiliateTrackingUrlTest extends TestCase
{
    public function test_it_selects_ios_url_for_iphone(): void
    {
        $campaign = new AffiliateCampaign(['tracking_url' => 'https://android.test', 'tracking_url_ios' => 'https://ios.test']);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone)']);

        $this->assertSame('https://ios.test', app(ResolveAffiliateTrackingUrl::class)->handle($campaign, $request));
    }

    public function test_it_selects_android_url_for_android(): void
    {
        $campaign = new AffiliateCampaign(['tracking_url' => 'https://android.test', 'tracking_url_ios' => 'https://ios.test']);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 14)']);

        $this->assertSame('https://android.test', app(ResolveAffiliateTrackingUrl::class)->handle($campaign, $request));
    }

    public function test_explicit_platform_overrides_user_agent(): void
    {
        $campaign = new AffiliateCampaign(['tracking_url' => 'https://android.test', 'tracking_url_ios' => 'https://ios.test']);
        $request = Request::create('/', 'POST', ['platform' => 'ios'], [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 14)']);

        $this->assertSame('https://ios.test', app(ResolveAffiliateTrackingUrl::class)->handle($campaign, $request));
    }
}
