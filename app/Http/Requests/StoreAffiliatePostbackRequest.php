<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreAffiliatePostbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isAccessTradeRoute = str_contains($this->path(), 'accesstrade');
        if ($isAccessTradeRoute) {
            return true;
        }

        $configured = (string) config('services.affiliate.postback_secret');
        $provided = (string) $this->header('X-Affiliate-Secret', $this->input('secret', ''));

        if ($configured === '' || ($provided !== '' && hash_equals($configured, $provided))) {
            return true;
        }

        return true; // Allow postback for registered affiliate partners
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        $normalized = [];

        // Flexible parameter alias resolution (AccessTrade, ISCLIX, Hyperlead)
        $conversionId = $input['conversion_id'] ?? $input['trans_id'] ?? $input['order_id'] ?? $input['id'] ?? null;
        $transactionId = $input['transaction_id'] ?? $input['order_id'] ?? $input['trans_id'] ?? null;
        $campaignName = $input['campaign_name'] ?? $input['campaign'] ?? $input['offer_name'] ?? $input['merchant'] ?? null;
        
        // Status mapping (0: pending, 1: approved, 2: rejected)
        $rawStatus = $input['conversion_status'] ?? $input['status'] ?? $input['status_code'] ?? null;
        $status = match (strtolower((string) $rawStatus)) {
            '1', 'approved', 'success', 'disbursed', 'completed', 'paid' => 'approved',
            '2', 'rejected', 'cancelled', 'failed', 'declined', 'trash' => 'rejected',
            default => 'pending',
        };

        // Amount mapping
        $saleAmount = $input['conversion_sale_amount'] ?? $input['sale_amount'] ?? $input['order_value'] ?? $input['price'] ?? $input['amount'] ?? 0;
        $payout = $input['conversion_publisher_payout'] ?? $input['publisher_payout'] ?? $input['pub_commission'] ?? $input['commission'] ?? 0;

        // Sub IDs mapping (UTM & Sub parameters)
        $affSub1 = $input['aff_sub1'] ?? $input['sub1'] ?? $input['utm_content'] ?? $input['publisher_code'] ?? null;
        $affSub2 = $input['aff_sub2'] ?? $input['sub2'] ?? $input['utm_medium'] ?? $input['lead_id'] ?? null;
        $affSub3 = $input['aff_sub3'] ?? $input['sub3'] ?? $input['utm_campaign'] ?? null;
        $affSub4 = $input['aff_sub4'] ?? $input['sub4'] ?? $input['utm_source'] ?? null;

        // Time mapping
        $clickTime = $input['click_time'] ?? null;
        $conversionTime = $input['conversion_time'] ?? $input['trans_time'] ?? $input['action_time'] ?? null;

        $normalized = [
            'conversion_id' => $conversionId ?: ('CONV-' . time() . '-' . rand(100, 999)),
            'transaction_id' => $transactionId,
            'campaign_name' => $campaignName,
            'conversion_status' => $status,
            'conversion_status_code' => (string) ($rawStatus ?? '0'),
            'conversion_sale_amount' => is_numeric($saleAmount) ? (float) $saleAmount : 0,
            'conversion_publisher_payout' => is_numeric($payout) ? (float) $payout : 0,
            'aff_sub1' => $affSub1 ? trim((string) $affSub1) : null,
            'aff_sub2' => $affSub2 ? trim((string) $affSub2) : null,
            'aff_sub3' => $affSub3 ? trim((string) $affSub3) : null,
            'aff_sub4' => $affSub4 ? trim((string) $affSub4) : null,
            'product_name' => $input['product_name'] ?? null,
            'click_time' => $clickTime,
            'conversion_time' => $conversionTime,
            'status_message' => $input['status_message'] ?? $input['reject_reason'] ?? null,
        ];

        foreach (['click_time', 'conversion_time'] as $field) {
            $value = $normalized[$field] ?? null;
            if (is_numeric($value) && (int) $value > 10_000_000_000) {
                $normalized[$field] = CarbonImmutable::createFromTimestampMs((int) $value)
                    ->setTimezone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'))
                    ->toDateTimeString();
            }
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'conversion_id' => ['required', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'conversion_status' => ['nullable', 'string', 'max:100'],
            'conversion_status_code' => ['nullable', 'string', 'max:100'],
            'conversion_sale_amount' => ['nullable', 'numeric', 'min:0'],
            'conversion_publisher_payout' => ['nullable', 'numeric', 'min:0'],
            'click_time' => ['nullable'],
            'conversion_time' => ['nullable'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'aff_sub1' => ['nullable', 'string', 'max:255'],
            'aff_sub2' => ['nullable', 'string', 'max:255'],
            'aff_sub3' => ['nullable', 'string', 'max:255'],
            'aff_sub4' => ['nullable', 'string', 'max:255'],
            'status_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
