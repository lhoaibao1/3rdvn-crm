<?php

namespace App\Support\Affiliate;

use App\Models\AffiliateConversion;
use App\Models\Lead;
use App\Models\User;
use App\Support\Notifications\AffiliateConversionNotificationSender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpsertAffiliateConversion
{
    public function handle(array $payload, string $partner = 'accesstrade'): AffiliateConversion
    {
        $conversion = DB::transaction(function () use ($payload, $partner): AffiliateConversion {
            $conversionId = (string) ($payload['conversion_id']
                ?? (($payload['offer_id'] ?? '').($payload['transaction_id'] ?? '')));
            $employeeCode = trim((string) ($payload['aff_sub1'] ?? ''));
            $user = $employeeCode !== ''
                ? User::query()
                    ->where('employee_code', $employeeCode)
                    ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
                    ->first()
                : null;

            $incoming = [
                'transaction_id' => $payload['transaction_id'] ?? null,
                'click_id' => $payload['click_id'] ?? null,
                'offer_id' => $payload['offer_id'] ?? null,
                'campaign_name' => $payload['campaign_name'] ?? $payload['offer_id'] ?? null,
                'conversion_status' => $payload['conversion_status'] ?? null,
                'conversion_status_code' => $payload['conversion_status_code'] ?? null,
                'sale_amount' => $payload['conversion_sale_amount'] ?? null,
                'publisher_payout' => $payload['conversion_publisher_payout'] ?? null,
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
                'event' => $payload['events'] ?? null,
                'status_message' => $payload['status_message'] ?? null,
                'shop_status_code' => $payload['shop_status_code'] ?? null,
                'publisher_id' => $payload['publisher_id'] ?? null,
                'conversion_date' => $payload['conversion_date'] ?? null,
                'conversion_modified_date' => $payload['conversion_modified_date'] ?? null,
                'click_date' => $payload['click_date'] ?? null,
                'created_by_id' => $user?->getKey(),
                'raw_payload' => Arr::except($payload, ['secret']),
            ];

            $existing = AffiliateConversion::query()
                ->where('partner', $partner)
                ->where('conversion_id', $conversionId)
                ->first();
            $data = collect($incoming)
                ->map(fn ($value, string $key) => $value ?? $existing?->{$key})
                ->all();
            $data['raw_payload'] = array_replace(
                (array) ($existing?->raw_payload ?? []),
                Arr::except($payload, ['secret']),
            );

            // Auto-link to Lead in CRM if aff_sub2 is a Lead ID
            $leadId = $payload['aff_sub2'] ?? $existing?->aff_sub2 ?? null;
            if (filled($leadId) && is_numeric($leadId)) {
                $lead = Lead::find((int) $leadId);
                if ($lead) {
                    $payloadData = (array) ($lead->payload ?? []);
                    $payloadData['transaction_id'] = $incoming['transaction_id'] ?: ($payloadData['transaction_id'] ?? null);
                    $payloadData['conversion_id'] = $conversionId;
                    $payloadData['conversion_status'] = $incoming['conversion_status'] ?: ($payloadData['conversion_status'] ?? null);
                    $payloadData['events'] = $incoming['event'] ?: ($payloadData['events'] ?? null);
                    $lead->payload = $payloadData;
                    $lead->saveQuietly();
                }
            }

            Log::info('Affiliate postback processed', [
                'partner' => $partner,
                'conversion_id' => $conversionId,
                'status' => $incoming['conversion_status'] ?? 'unknown',
                'amount' => $incoming['sale_amount'] ?? 0,
                'employee_code' => $employeeCode,
                'employee_found' => $user ? true : false,
                'publisher_id' => $incoming['publisher_id'] ?? null,
            ]);

            return AffiliateConversion::query()->updateOrCreate(
                ['partner' => $partner, 'conversion_id' => $conversionId],
                $data,
            );
        });

        if ($conversion->wasRecentlyCreated || $conversion->wasChanged(['conversion_status', 'sale_amount', 'transaction_id'])) {
            AffiliateConversionNotificationSender::changed($conversion);
        }

        return $conversion;
    }

    private function normalizePartnerTime(mixed $value): mixed
    {
        if (is_numeric($value) && (int) $value > 10_000_000_000) {
            return CarbonImmutable::createFromTimestampMs((int) $value)
                ->setTimezone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'))
                ->toDateTimeString();
        }

        return $value;
    }
}
