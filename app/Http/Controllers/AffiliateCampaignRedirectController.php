<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCampaign;
use App\Models\AffiliateClick;
use App\Models\Lead;
use App\Models\User;
use App\Services\Affiliate\ResolveAffiliateTrackingUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AffiliateCampaignRedirectController extends Controller
{
    public function __invoke(Request $request, AffiliateCampaign $campaign, ResolveAffiliateTrackingUrl $trackingUrls): View|RedirectResponse
    {
        $rawRef = trim((string) $request->query('ref'));
        $sub2 = trim((string) $request->query('sub2', ''));
        $employeeCode = $rawRef;

        // Support NNF partner traffic formats:
        // - ref=NNF... -> employeeCode = RD260076, sub2 = NNF...
        // - ref=RD260076_NNF... -> employeeCode = RD260076, sub2 = NNF...
        // - ref=RD260076 & sub2=NNF...
        if (strtoupper($employeeCode) === 'NNF' || str_starts_with(strtoupper($employeeCode), 'NNF')) {
            if ($sub2 === '') {
                $sub2 = $employeeCode;
            }
            $employeeCode = 'RD260076';
        } elseif (str_contains($employeeCode, '_')) {
            [$prefix, $suffix] = explode('_', $employeeCode, 2);
            if (strtoupper($prefix) === 'RD260076') {
                $employeeCode = 'RD260076';
                if ($sub2 === '') {
                    $sub2 = $suffix;
                }
            }
        }

        abort_unless(strtoupper($employeeCode) === 'RD260076', 403, 'Hệ thống bảo trì không hẹn ngày mở lại. Đã gửi email hệ thống mới, vui lòng liên hệ AM để được hỗ trợ.');

        abort_if($employeeCode === '', 404);

        if (! $campaign->isOpen()) {
            return view('affiliate.campaign_closed', [
                'campaign' => $campaign,
                'message' => $campaign->closureReason(),
            ]);
        }

        $validUser = User::query()
            ->where('employee_code', $employeeCode)
            ->when(strtoupper($employeeCode) !== 'RD260076', fn ($query) => $query->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED]))
            ->first();

        abort_unless($validUser, 404);

        $userAgent = (string) $request->userAgent();
        $isBot = $this->isLinkPreviewCrawler($userAgent);

        $isNnfTraffic = ($validUser->employee_code === 'RD260076')
            || str_starts_with(strtoupper($rawRef), 'NNF')
            || ($sub2 !== '' && str_starts_with(strtoupper($sub2), 'NNF'));

        // Build target affiliate fallback URL
        $partner = match(true) {
            str_contains(strtolower($campaign->slug . $campaign->name), 'vpbank') => 'isclix',
            str_contains(strtolower($campaign->slug . $campaign->name), 'tinvay'),
            str_contains(strtolower($campaign->slug . $campaign->name), 'shinhan') => 'accesstrade',
            default => 'hyperlead',
        };

        $trackingUrl = $trackingUrls->handle($campaign, $request);
        $this->guardVpbankTrackingUrl($campaign, $trackingUrl);
        $separator = str_contains($trackingUrl, '?') ? '&' : '?';

        $sub1Val = $validUser->employee_code;
        if ($isNnfTraffic && $sub2 !== '') {
            $sub1Val = $validUser->employee_code . '_' . $sub2;
        }

        $fallbackParams = [
            'aff_sub1' => $sub1Val,
            'sub1' => $sub1Val,
            'utm_content' => $sub2 !== '' ? $sub2 : $validUser->employee_code,
            'utm_source' => $isNnfTraffic ? 'nnf' : '3rdvn',
            'utm_medium' => 'affiliate',
            'utm_campaign' => $campaign->slug,
        ];
        if ($sub2 !== '') {
            $fallbackParams['aff_sub2'] = $sub2;
            $fallbackParams['sub2'] = $sub2;
        }
        $sub3 = trim((string) $request->query('sub3', ''));
        if ($sub3 !== '') {
            $fallbackParams['aff_sub3'] = $sub3;
            $fallbackParams['sub3'] = $sub3;
        }
        $sub4 = trim((string) $request->query('sub4', ''));
        if ($sub4 !== '') {
            $fallbackParams['aff_sub4'] = $sub4;
            $fallbackParams['sub4'] = $sub4;
        }

        $targetUrl = $trackingUrl . $separator . http_build_query($fallbackParams);

        // White-label metadata per campaign (no 3RD mentions)
        $campSlug = strtolower($campaign->slug ?: '');
        $campName = $campaign->name ?: 'Chiến Dịch Tiếp Thị';

        if (str_contains($campSlug, 'shb')) {
            $ogTitle = 'SHB Finance — Vay Tiêu Dùng Tín Chấp Trực Tuyến';
            $ogDescription = 'Gói vay tiêu dùng tín chấp SHB Finance hạn mức lên đến 100 Triệu VNĐ. Đăng ký online 100%, thủ tục nhanh chóng, bảo mật tuyệt đối.';
            $ogImage = 'https://3rdvn.io.vn/images/logo-shb.png';
            $campaignName = 'SHB Finance';
        } elseif (str_contains($campSlug, 'vpbank')) {
            $ogTitle = 'VPBank UPL — Vay Vốn Tiêu Dùng Trực Tuyến Ngân Hàng VPBank';
            $ogDescription = 'Đăng ký vay tín chấp tiêu dùng ngân hàng VPBank online 100%, hạn mức đến 200 Triệu VNĐ, xét duyệt tự động nhanh chóng.';
            $ogImage = 'https://3rdvn.io.vn/images/logo-vpbank.jpg';
            $campaignName = 'VPBank UPL';
        } elseif (str_contains($campSlug, 'shinhan')) {
            $ogTitle = "{$campaign->name} — Vay Tài Tốc Online 100%";
            $ogDescription = 'Đăng ký vay Tài Tốc trên ứng dụng iShinhan, quy trình tự động, nhanh gọn và an toàn.';
            $ogImage = 'https://pub2-aff.3rdvn.io.vn/static/logo-shinhan.svg';
            $campaignName = $campaign->name;
        } elseif (str_contains($campSlug, 'tinvay')) {
            $ogTitle = 'Tin Vay — Vay Tiền Mặt Online Siêu Tốc (VietCredit)';
            $ogDescription = 'Hạn mức 5 - 100 Triệu VNĐ, duyệt tự động chỉ với CCCD gắn chip. Đăng ký nhận tiền nhanh chóng trong ngày.';
            $ogImage = 'https://3rdvn.io.vn/images/logo-tinvay.png';
            $campaignName = 'Tin Vay';
        } else {
            $ogTitle = "{$campName} — Cổng Đăng Ký Trực Tuyến";
            $ogDescription = 'Hỗ trợ tư vấn và đăng ký hồ sơ tài chính trực tuyến nhanh chóng, bảo mật.';
            $ogImage = 'https://3rdvn.io.vn/images/logo-3rdvn.jpg';
            $campaignName = $campName;
        }

        // Record Click in affiliate_clicks for all incoming traffic (real users)
        if (! $isBot) {
            try {
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
                Log::error('Failed to log affiliate click: '.$e->getMessage());
            }
        }

        // Tin Vay OR any partner traffic (NNF): skip 3RD landing form so the customer
        // reaches partner in one hop without seeing 3RD VN landing page!
        if (($isNnfTraffic || str_contains($campSlug, 'tinvay')) && ! $isBot) {
            return redirect()->away($targetUrl);
        }

        // Render Landing Form for real users to capture Họ tên, SĐT, CCCD, Ngày sinh
        return view('affiliate.landing_capture', [
            'isBot' => $isBot,
            'campaign' => $campaign,
            'campaignName' => $campaignName,
            'ogTitle' => $ogTitle,
            'ogDescription' => $ogDescription,
            'ogImage' => $ogImage,
            'currentUrl' => $request->fullUrl(),
            'targetUrl' => $targetUrl,
            'employeeCode' => $validUser->employee_code,
        ]);
    }

    public function submit(Request $request, AffiliateCampaign $campaign, ResolveAffiliateTrackingUrl $trackingUrls): JsonResponse|RedirectResponse
    {
        $campaign = $campaign->fresh();
        if (! $campaign->isOpen()) {
            $message = $campaign->closureReason();
            if (! $request->expectsJson() && ! $request->ajax()) {
                return back()->withErrors(['campaign' => $message]);
            }

            return response()->json(['success' => false, 'message' => $message], 423);
        }

        $rawRef = trim((string) $request->input('ref', ''));
        $sub2 = trim((string) $request->input('sub2', ''));
        $employeeCode = $rawRef;

        if (strtoupper($employeeCode) === 'NNF' || str_starts_with(strtoupper($employeeCode), 'NNF')) {
            if ($sub2 === '') {
                $sub2 = $employeeCode;
            }
            $employeeCode = 'RD260076';
        } elseif (str_contains($employeeCode, '_')) {
            [$prefix, $suffix] = explode('_', $employeeCode, 2);
            if (strtoupper($prefix) === 'RD260076') {
                $employeeCode = 'RD260076';
                if ($sub2 === '') {
                    $sub2 = $suffix;
                }
            }
        }

        abort_unless(strtoupper($employeeCode) === 'RD260076', 403, 'Hệ thống bảo trì không hẹn ngày mở lại. Đã gửi email hệ thống mới, vui lòng liên hệ AM để được hỗ trợ.');

        if ($employeeCode === '') {
            if (! $request->expectsJson() && ! $request->ajax()) {
                return back()->withErrors(['ref' => 'Thiếu mã nhân viên giới thiệu.']);
            }
            return response()->json(['success' => false, 'message' => 'Thiếu mã nhân viên giới thiệu.'], 422);
        }

        $saleUser = User::query()
            ->where('employee_code', $employeeCode)
            ->when(strtoupper($employeeCode) !== 'RD260076', fn ($query) => $query->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED]))
            ->first();

        if (! $saleUser) {
            if (! $request->expectsJson() && ! $request->ajax()) {
                return back()->withErrors(['ref' => 'Mã nhân viên giới thiệu không tồn tại hoặc đã ngưng hoạt động.']);
            }
            return response()->json(['success' => false, 'message' => 'Mã nhân viên giới thiệu không tồn tại.'], 422);
        }

        $rawName = $request->input('full_name') ?? $request->input('name') ?? '';
        $name = mb_strtoupper(trim((string) $rawName), 'UTF-8');
        $phone = preg_replace('/[^0-9]/', '', (string) $request->input('phone', ''));
        $idNumber = preg_replace('/[^0-9]/', '', (string) $request->input('identity_number', ''));
        $dob = trim((string) $request->input('date_of_birth', ''));

        if ($name === '' || mb_strlen($name) < 3) {
            if (! $request->expectsJson() && ! $request->ajax()) {
                return back()->withErrors(['full_name' => 'Vui lòng nhập họ và tên hợp lệ.']);
            }
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập họ và tên hợp lệ.'], 422);
        }

        if (! preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $phone)) {
            if (! $request->expectsJson() && ! $request->ajax()) {
                return back()->withErrors(['phone' => 'Số điện thoại phải gồm 10 chữ số hợp lệ (đầu số 03, 05, 07, 08, 09).']);
            }
            return response()->json(['success' => false, 'message' => 'Số điện thoại phải gồm 10 chữ số hợp lệ.'], 422);
        }

        if ($idNumber === '' || (strlen($idNumber) !== 12 && strlen($idNumber) !== 9)) {
            if (! $request->expectsJson() && ! $request->ajax()) {
                return back()->withErrors(['identity_number' => 'Số CCCD/CMND không hợp lệ.']);
            }
            return response()->json(['success' => false, 'message' => 'Số CCCD/CMND không hợp lệ.'], 422);
        }

        $partner = match(true) {
            str_contains(strtolower($campaign->slug . $campaign->name), 'vpbank') => 'isclix',
            str_contains(strtolower($campaign->slug . $campaign->name), 'tinvay'),
            str_contains(strtolower($campaign->slug . $campaign->name), 'shinhan') => 'accesstrade',
            default => 'hyperlead',
        };

        // 1. Ghi nhận lượt Click
        $click = null;
        try {
            $click = AffiliateClick::create([
                'campaign_id' => $campaign->id,
                'campaign_slug' => $campaign->slug,
                'campaign_name' => $campaign->name,
                'partner' => $partner,
                'employee_code' => $saleUser->employee_code,
                'user_id' => $saleUser->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'referer' => substr((string) $request->header('referer'), 0, 500),
                'clicked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log affiliate click on submit: ' . $e->getMessage());
        }

        // 2. Tạo bản ghi Lead lưu vào CRM
        $trackingToken = Str::random(32);
        try {
            $lead = Lead::create([
                'lead_name' => $name,
                'phone' => $phone,
                'source' => 'affiliate_landing',
                'status' => 'new',
                'assigned_sale_id' => $saleUser->id,
                'created_by_id' => $saleUser->id,
                'team_id' => $saleUser->team_id,
                'team_leader_id' => $saleUser->team_leader_id,
                'am_id' => $saleUser->am_id,
                'zd_id' => $saleUser->zd_id,
                'note' => "Đăng ký từ Link Affiliate {$campaign->name}. CCCD: {$idNumber}, Ngày sinh: {$dob}",
                'payload' => [
                    'identity_number' => $idNumber,
                    'date_of_birth' => $dob,
                    'campaign_id' => $campaign->id,
                    'campaign_slug' => $campaign->slug,
                    'campaign_name' => $campaign->name,
                    'click_id' => $click?->id,
                    'affiliate_tracking_token' => $trackingToken,
                    'aff_sub1' => $saleUser->employee_code,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            $payloadData = $lead->payload;
            $payloadData['aff_sub2'] = (string) $lead->id;
            $lead->payload = $payloadData;
            $lead->saveQuietly();

            $leadId = (string) $lead->id;
        } catch (\Throwable $e) {
            Log::error('Failed to create lead from affiliate landing: ' . $e->getMessage());
            $leadId = '';
        }

        // 3. Xây dựng đường dẫn chuyển tiếp sang đối tác
        $trackingUrl = $trackingUrls->handle($campaign, $request);
        $this->guardVpbankTrackingUrl($campaign, $trackingUrl);
        $separator = str_contains($trackingUrl, '?') ? '&' : '?';

        $isNnfTraffic = ($saleUser->employee_code === 'RD260076')
            || str_starts_with(strtoupper($rawRef), 'NNF')
            || ($sub2 !== '' && str_starts_with(strtoupper($sub2), 'NNF'));

        $sub1Val = $saleUser->employee_code;
        if ($isNnfTraffic && $sub2 !== '') {
            $sub1Val = $saleUser->employee_code . '_' . $sub2;
        }

        $params = [
            'aff_sub1' => $sub1Val,
            'sub1' => $sub1Val,
            'aff_sub2' => $leadId,
            'sub2' => $leadId,
            'aff_sub3' => $trackingToken,
            'sub3' => $trackingToken,
            'utm_content' => $sub2 !== '' ? $sub2 : $saleUser->employee_code,
            'utm_medium' => $leadId,
            'utm_campaign' => $campaign->slug,
            'utm_source' => $isNnfTraffic ? 'nnf' : '3rdvn',
        ];
        if ($sub2 !== '') {
            $params['aff_sub4'] = $sub2;
            $params['sub4'] = $sub2;
        }

        if (str_contains(strtolower($campaign->slug . $campaign->name), 'tinvay')) {
            $trackingUrl = $this->withTinVayPrefill($trackingUrl, [
                'name' => $name,
                'phone' => $phone,
                'nationalId' => $idNumber,
                'birthday' => $dob,
            ]);
            $separator = str_contains($trackingUrl, '?') ? '&' : '?';
        }

        $targetUrl = $trackingUrl . $separator . http_build_query($params);

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->away($targetUrl);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tiếp nhận thông tin thành công.',
            'lead_id' => $leadId,
            'redirect_url' => $targetUrl,
        ]);
    }

    private function isLinkPreviewCrawler(?string $userAgent): bool
    {
        $ua = (string) $userAgent;
        if ($ua === '') {
            return false;
        }

        if (preg_match('/Zalo iOS\/|ZaloAndroid|Zalo Android|FBAN|FBAV|FB_IAB|(Mozilla.+WhatsApp)/i', $ua)) {
            return false;
        }

        return (bool) preg_match('/facebookexternalhit|facebot|zaloshare|zalobot|telegrambot|twitterbot|whatsapp|slackbot|linkedinbot|discordbot|skypeuripreview|googlebot|bingbot|baiduspider|yandexbot|applebot|crawler|spider|bot|meta-externalagent|developers\.google\.com/i', $ua);
    }

    private function withTinVayPrefill(string $trackingUrl, array $prefill): string
    {
        $parts = parse_url($trackingUrl);
        if (! is_array($parts) || empty($parts['host'])) {
            return $trackingUrl;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $encoded = (string) ($query['url_enc'] ?? '');
        $dest = $encoded !== '' ? (string) base64_decode(strtr($encoded, '-_', '+/'), true) : '';
        if ($dest === '' || ! str_starts_with($dest, 'http')) {
            $dest = 'https://tinvay.vietcredit.com.vn/';
        }

        $destParts = parse_url($dest) ?: [];
        parse_str((string) ($destParts['query'] ?? ''), $destQuery);
        foreach ($prefill as $key => $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $destQuery[$key] = $value;
            }
        }

        $newDest = ($destParts['scheme'] ?? 'https').'://'.($destParts['host'] ?? 'tinvay.vietcredit.com.vn').($destParts['path'] ?? '/');
        if ($destQuery) {
            $newDest .= '?'.http_build_query($destQuery);
        }
        $query['url_enc'] = base64_encode($newDest);

        return ($parts['scheme'] ?? 'https').'://'.($parts['host']).($parts['path'] ?? '').'?'.http_build_query($query);
    }

    private function guardVpbankTrackingUrl(AffiliateCampaign $campaign, string $trackingUrl): void
    {
        if (! str_contains(strtolower($campaign->slug.' '.$campaign->name), 'vpbank')) {
            return;
        }

        $host = strtolower((string) parse_url($trackingUrl, PHP_URL_HOST));
        $allowedHosts = ['go.isclix.com', 'fast.accesstrade.com.vn', 'click.accesstrade.vn'];

        if (! in_array($host, $allowedHosts, true)) {
            Log::critical('Blocked invalid VPBank affiliate redirect', [
                'campaign_id' => $campaign->id,
                'campaign_slug' => $campaign->slug,
                'target_host' => $host,
            ]);

            abort(503, 'Liên kết VPBank đang được kiểm tra. Vui lòng thử lại sau.');
        }
    }
}
