<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAffiliatePostbackRequest;
use App\Models\AffiliateCampaign;
use App\Support\Affiliate\UpsertAffiliateConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AffiliatePostbackController extends Controller
{
    public function __invoke(StoreAffiliatePostbackRequest $request, UpsertAffiliateConversion $action): JsonResponse
    {
        $routeCampaign = (string) ($request->route('campaign') ?: $request->input('campaign', ''));
        $routePartner = (string) ($request->route('affiliate_partner') ?: $request->input('partner', ''));

        $campaignName = null;
        $partner = 'accesstrade';

        if ($routeCampaign !== '') {
            $slugLower = strtolower($routeCampaign);
            if (str_contains($slugLower, 'vpbank')) {
                $campaignName = 'VPBank UPL';
                $partner = 'isclix';
            } elseif (str_contains($slugLower, 'tinvay') || str_contains($slugLower, 'vietcredit')) {
                $campaignName = 'TinVay · VietCredit';
                $partner = 'accesstrade';
            } elseif (str_contains($slugLower, 'shb')) {
                $campaignName = 'SHB Finance';
                $partner = 'hyperlead';
            } else {
                $dbCamp = AffiliateCampaign::where('slug', $routeCampaign)->orWhere('name', 'ilike', "%{$routeCampaign}%")->first();
                if ($dbCamp) {
                    $campaignName = $dbCamp->name;
                }
            }
        }

        if ($routePartner !== '') {
            $partner = strtolower($routePartner);
        }

        $payload = $request->validated();
        if ($campaignName && empty($payload['campaign_name'])) {
            $payload['campaign_name'] = $campaignName;
        }

        Log::info('Affiliate Postback received', [
            'route_campaign' => $routeCampaign,
            'partner' => $partner,
            'campaign_name' => $payload['campaign_name'] ?? '-',
            'conversion_id' => $payload['conversion_id'] ?? '-',
            'status' => $payload['conversion_status'] ?? '-',
            'amount' => $payload['conversion_sale_amount'] ?? 0,
            'sub1' => $payload['aff_sub1'] ?? '-',
        ]);

        try {
            $conversion = $action->handle($payload, $partner);

            // Fire real-time Web Push broadcast to node server
            try {
                $sub1 = trim((string) ($conversion->aff_sub1 ?? ''));
                $campTitle = $conversion->campaign_name ?: 'Chiến dịch tiếp thị';
                $statusStr = strtolower((string) $conversion->conversion_status);
                $isApproved = in_array($statusStr, ['success', 'approved', 'disbursed', 'completed', 'paid']);
                $amountFormatted = $conversion->sale_amount ? number_format($conversion->sale_amount, 0, ',', '.') . ' đ' : '';

                $pushTitle = $isApproved ? "🎉 Giải Ngân Thành Công ({$campTitle})" : "🔔 Đơn Mới: {$campTitle}";
                $pushBody = $isApproved
                    ? "Đơn hàng {$conversion->conversion_id} đã được duyệt giải ngân {$amountFormatted}!"
                    : "Khách hàng vừa nộp hồ sơ thành công qua link {$campTitle}.";

                \Illuminate\Support\Facades\Http::timeout(2)->post('http://127.0.0.1:3070/api/internal/push-broadcast', [
                    'user_code' => $sub1,
                    'title' => $pushTitle,
                    'body' => $pushBody,
                    'icon' => '/static/logo.jpg',
                    'url' => '/?tab=reports',
                ]);
            } catch (\Throwable $pushErr) {
                // Non-blocking
            }

            return response()->json([
                'ok' => true,
                'status' => 'success',
                'id' => $conversion->getKey(),
                'partner' => $partner,
                'campaign' => $conversion->campaign_name,
                'conversion_id' => $conversion->conversion_id,
                'sale_amount' => $conversion->sale_amount,
                'conversion_status' => $conversion->conversion_status,
                'message' => 'Ghi nhận chuyển đổi thành công',
            ]);
        } catch (\Throwable $e) {
            Log::error('Affiliate Postback failed', [
                'partner' => $partner,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
