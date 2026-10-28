<?php

namespace App\Support\Affiliate;

use App\Models\AffiliateConversion;
use App\Models\AffiliateCampaign;
use App\Models\Lead;
use App\Models\User;
use App\Support\AffiliateConversionStatus;
use App\Support\Notifications\AffiliateConversionNotificationSender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpsertAffiliateConversion
{
    public function handle(array $payload, string $partner = 'accesstrade'): AffiliateConversion
    {
        $conversionId = (string) ($payload['conversion_id']
            ?? (($payload['offer_id'] ?? '').($payload['transaction_id'] ?? '')));

        $existing = $this->findExistingConversion($payload, $partner, $conversionId);
        $canonicalConversionId = (string) ($existing?->conversion_id ?: $conversionId);
        $isCrossIdentityMatch = $existing && $canonicalConversionId !== $conversionId;

        $oldStatus = $existing ? strtolower((string) ($existing->conversion_status ?? '')) : null;

        $conversion = DB::transaction(function () use ($payload, $partner, $conversionId, $canonicalConversionId, $existing, $isCrossIdentityMatch): AffiliateConversion {
            $employeeCode = trim((string) ($payload['aff_sub1'] ?? ''));
            $user = $employeeCode !== ''
                ? User::query()
                    ->where('employee_code', $employeeCode)
                    ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
                    ->first()
                : null;

            // Chuẩn hóa tên chiến dịch chính xác 100%
            $rawCamp = strtolower((string)($payload['campaign_name'] ?? '') . ($payload['offer_id'] ?? '') . ($payload['landing_page'] ?? '') . $partner . $conversionId);
            $normalizedCampaign = match(true) {
                str_contains($rawCamp, 'vpbank') => 'VPBank UPL',
                str_contains($rawCamp, 'shb') || $partner === 'hyperlead' => 'SHB Finance',
                str_contains($rawCamp, 'tinvay') || str_contains($rawCamp, 'vietcredit') => 'Tin Vay',
                default => $payload['campaign_name'] ?? ($payload['offer_id'] ?? 'SHB Finance'),
            };

            $rawPayload = Arr::except($payload, ['secret']);
            if ($isCrossIdentityMatch) {
                // AccessTrade report rows use a hash as conversion_id while the
                // VPBank callback identifies that same order by order_id. Keep
                // the report identity/customer payload as the canonical source.
                $rawPayload['postback_conversion_id'] = $conversionId;
                $rawPayload['postback_transaction_id'] = $payload['transaction_id'] ?? null;
                unset($rawPayload['conversion_id'], $rawPayload['transaction_id']);
            }

            $incoming = [
                'transaction_id' => $isCrossIdentityMatch
                    ? $existing?->transaction_id
                    : ($payload['transaction_id'] ?? null),
                'click_id' => $payload['click_id'] ?? null,
                'offer_id' => $payload['offer_id'] ?? null,
                'campaign_name' => $normalizedCampaign,
                'conversion_status' => $payload['conversion_status'] ?? null,
                'conversion_status_code' => $payload['conversion_status_code'] ?? null,
                'sale_amount' => $payload['conversion_sale_amount'] ?? ($payload['sale_amount'] ?? ($payload['order_value'] ?? ($payload['price'] ?? ($payload['amount'] ?? ($payload['approved_amount'] ?? ($payload['disbursed_amt'] ?? null)))))),
                'publisher_payout' => $payload['conversion_publisher_payout'] ?? ($payload['publisher_payout'] ?? ($payload['pub_commission'] ?? ($payload['commission'] ?? null))),
                'click_time' => $this->normalizePartnerTime($payload['click_time'] ?? null),
                'conversion_time' => $this->normalizePartnerTime($payload['conversion_time'] ?? null),
                'conversion_modified_time' => $this->normalizePartnerTime($payload['conversion_modified_time'] ?? null),
                'conversion_status_updated_time' => $this->normalizePartnerTime($payload['conversion_status_updated_time'] ?? null),
                'product_name' => $payload['product_name'] ?? null,
                'product_url' => $payload['product_url'] ?? null,
                'product_sku' => $payload['product_sku'] ?? null,
                'product_category_id' => $payload['product_category_id'] ?? null,
                'product_category' => $payload['product_category'] ?? null,
                'aff_sub1' => $payload['aff_sub1'] ?? null,
                'aff_sub2' => $payload['aff_sub2'] ?? null,
                'aff_sub3' => $payload['aff_sub3'] ?? null,
                'aff_sub4' => $payload['aff_sub4'] ?? null,
                'landing_page' => $payload['landing_page'] ?? null,
                'event' => $payload['events'] ?? ($payload['event'] ?? null),
                'status_message' => $payload['status_message'] ?? null,
                'shop_status_code' => $payload['shop_status_code'] ?? null,
                'publisher_id' => $payload['publisher_id'] ?? null,
                'conversion_date' => $payload['conversion_date'] ?? null,
                'conversion_modified_date' => $payload['conversion_modified_date'] ?? null,
                'click_date' => $payload['click_date'] ?? null,
                'created_by_id' => $user?->getKey() ?: ($existing?->created_by_id),
                'raw_payload' => $rawPayload,
            ];

            // 100% ĐỐI CHIẾU DỮ LIỆU KHÁCH HÀNG TỪ LEAD CRM QUA AFF_SUB2
            $leadId = $payload['aff_sub2'] ?? $existing?->aff_sub2 ?? null;
            if (filled($leadId) && is_numeric($leadId)) {
                $lead = Lead::find((int) $leadId);
                if ($lead) {
                    $leadPayload = (array) ($lead->payload ?? []);
                    $campaign = AffiliateCampaign::query()->where('name', $normalizedCampaign)->first();
                    $expectedCampaignId = data_get($leadPayload, 'campaign_id');
                    $expectedToken = (string) data_get($leadPayload, 'affiliate_tracking_token', '');
                    $rawToken = $payload['aff_sub3']
                        ?? $payload['sub3']
                        ?? data_get($payload, '_extra.sub_params.sub3')
                        ?? data_get($existing?->raw_payload, '_extra.sub_params.sub3')
                        ?? $existing?->aff_sub3
                        ?? '';
                    $receivedToken = trim((string) $rawToken);
                    $campaignMatches = ! $expectedCampaignId || ! $campaign || (int) $expectedCampaignId === (int) $campaign->id;
                    $tokenMatches = $expectedToken === ''
                        || $receivedToken === ''
                        || strcasecmp($receivedToken, 'sub3') === 0
                        || strcasecmp($receivedToken, $normalizedCampaign) === 0
                        || (isset($campaign?->slug) && strcasecmp($receivedToken, (string) $campaign->slug) === 0)
                        || hash_equals($expectedToken, $receivedToken);

                    if (! $campaignMatches || ! $tokenMatches) {
                        $incoming['raw_payload']['attribution_verified'] = false;
                        $incoming['raw_payload']['attribution_error'] = ! $campaignMatches
                            ? 'campaign_mismatch'
                            : 'tracking_token_mismatch';
                        Log::warning('Affiliate conversion attribution rejected', [
                            'conversion_id' => $canonicalConversionId,
                            'lead_id' => $leadId,
                            'campaign' => $normalizedCampaign,
                            'reason' => $incoming['raw_payload']['attribution_error'],
                        ]);
                    } else {
                    $incoming['raw_payload']['attribution_verified'] = true;
                    $customerName = $lead->lead_name;
                    $customerPhone = $lead->phone;
                    $customerCccd = is_array($lead->payload) ? ($lead->payload['identity_number'] ?? null) : null;
                    $customerDob = is_array($lead->payload) ? ($lead->payload['date_of_birth'] ?? null) : null;

                    $incoming['raw_payload']['customer_name'] = $customerName;
                    $incoming['raw_payload']['customer_phone'] = $customerPhone;
                    $incoming['raw_payload']['customer_identity_number'] = $customerCccd;
                    $incoming['raw_payload']['customer_dob'] = $customerDob;

                    // Cập nhật trạng thái và đối chiếu mã đơn trong CRM Lead
                    $payloadData = (array) ($lead->payload ?? []);
                    $payloadData['transaction_id'] = $incoming['transaction_id'] ?: ($payloadData['transaction_id'] ?? null);
                    $payloadData['conversion_id'] = $canonicalConversionId;
                    $payloadData['conversion_status'] = $incoming['conversion_status'] ?: ($payloadData['conversion_status'] ?? null);
                    $payloadData['events'] = $incoming['event'] ?: ($payloadData['events'] ?? null);
                    $payloadData['sale_amount'] = $incoming['sale_amount'] ?: ($payloadData['sale_amount'] ?? null);
                    $lead->payload = $payloadData;

                    $statusLower = strtolower((string)$incoming['conversion_status']);
                    if (in_array($statusLower, ['approved', 'success', 'disbursed', 'completed', 'paid'], true)) {
                        $lead->status = 'Khách hàng thoả mãn điều kiện';
                    } elseif (in_array($statusLower, ['rejected', 'cancelled', 'failed', 'declined'], true)) {
                        $lead->status = 'Từ chối';
                    }
                    $lead->saveQuietly();
                    }
                }
            }

            $data = collect($incoming)
                ->map(fn ($value, string $key) => $value ?? $existing?->{$key})
                ->all();

            $mergedPayload = array_replace(
                (array) ($existing?->raw_payload ?? []),
                $incoming['raw_payload'],
            );
            $data['raw_payload'] = $mergedPayload;

            return AffiliateConversion::query()->updateOrCreate(
                ['partner' => $partner, 'conversion_id' => $canonicalConversionId],
                $data,
            );
        });

        $newStatus = strtolower((string) ($conversion->conversion_status ?? ''));
        $isBrandNew = ! $existing;
        $statusActuallyChanged = $existing && ($oldStatus !== $newStatus);
        $businessPhaseChanged = $existing
            && AffiliateConversionStatus::phase($oldStatus, $existing->sale_amount)
                !== AffiliateConversionStatus::phase($newStatus, $conversion->sale_amount);

        // Amount can move a pending partner record into approved/waiting-disbursement.
        if ($isBrandNew || $statusActuallyChanged || $businessPhaseChanged) {
            AffiliateConversionNotificationSender::changed($conversion, $oldStatus);
        }

        return $conversion;
    }

    private function findExistingConversion(array $payload, string $partner, string $conversionId): ?AffiliateConversion
    {
        $exact = AffiliateConversion::query()
            ->where('partner', $partner)
            ->where('conversion_id', $conversionId)
            ->first();

        if ($exact || ! in_array($partner, ['isclix', 'accesstrade'], true)) {
            return $exact;
        }

        $partnerConversionId = trim((string) ($payload['transaction_id'] ?? ''));
        if ($conversionId === '' || $partnerConversionId === '') {
            return null;
        }

        return AffiliateConversion::query()
            ->where('partner', $partner)
            ->where(function ($q) use ($conversionId, $partnerConversionId) {
                if ($conversionId !== '') {
                    $q->where('conversion_id', $conversionId)
                      ->orWhere('transaction_id', $conversionId);
                }
                if ($partnerConversionId !== '') {
                    $q->orWhere('conversion_id', $partnerConversionId)
                      ->orWhere('transaction_id', $partnerConversionId);
                }
            })
            ->first();
    }

    private function normalizePartnerTime(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || in_array(strtolower($value), [
            'click_time',
            'conversion_time',
            'trans_time',
            'transaction_time',
            'action_time',
            'update_time',
        ], true)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $timestamp = (int) $value;

                return ($timestamp > 10_000_000_000
                    ? CarbonImmutable::createFromTimestampMs($timestamp)
                    : CarbonImmutable::createFromTimestamp($timestamp))
                    ->setTimezone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'))
                    ->toDateTimeString();
            }

            return CarbonImmutable::parse($value)
                ->setTimezone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'))
                ->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
