<?php

namespace App\Console\Commands;

use App\Models\AffiliateConversion;
use App\Models\User;
use App\Support\Affiliate\TinVayOrderId;
use App\Support\Notifications\AffiliateConversionNotificationSender;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncAccessTradeOrders extends Command
{
    protected $signature = 'affiliate:sync-accesstrade {--days=30}';
    protected $description = 'Sync transactions directly from AccessTrade API';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = Carbon::now()->subDays($days)->format('Y-m-d');
        // AccessTrade's `until` boundary is not consistently inclusive. Fetch
        // through tomorrow so transactions created today are never delayed.
        $until = Carbon::now()->addDay()->format('Y-m-d');
        $apiKey = '4PIctagU6THxrsB-aipcKXZyFGn4zmig';

        $this->info("Fetching AccessTrade transactions from {$since} to {$until}...");

        $transactionsById = [];
        $page = 1;
        $total = null;

        do {
            $response = null;
            for ($attempt = 1; $attempt <= 4; $attempt++) {
                $response = Http::withHeaders([
                    'Authorization' => "Token {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(30)->get('https://api.accesstrade.vn/v1/transactions', [
                    'since' => $since,
                    'until' => $until,
                    'limit' => 500,
                    'page' => $page,
                ]);

                if ($response->status() !== 429) {
                    break;
                }

                // Partner API is limited to 10 requests/minute. A temporary
                // throttle must delay synchronization, never drop the batch.
                sleep(15 * $attempt);
            }

            if (! $response?->successful()) {
                $this->error('AccessTrade API Error: '.($response?->status() ?? 0).' - '.($response?->body() ?? 'No response'));
                return self::FAILURE;
            }

            $json = $response->json();
            $batch = $json['data'] ?? [];
            $total ??= (int) ($json['total'] ?? count($batch));

            $countBeforePage = count($transactionsById);
            foreach ($batch as $index => $transaction) {
                $key = (string) ($transaction['id'] ?? $transaction['conversion_id'] ?? $transaction['transaction_id'] ?? "{$page}:{$index}");
                $transactionsById[$key] = $transaction;
            }

            if (count($batch) > 0 && count($transactionsById) === $countBeforePage && count($transactionsById) < $total) {
                $this->error('AccessTrade pagination returned the same page twice; synchronization stopped safely.');
                return self::FAILURE;
            }

            $page++;
        } while (count($batch) > 0 && count($transactionsById) < $total);

        $transactions = array_values($transactionsById);
        $this->info("Found " . count($transactions) . " transactions.");

        $syncedCount = 0;
        $unchangedCount = 0;
        $ignoredCount = 0;
        foreach ($transactions as $t) {
            $conversionId = (string) ($t['id'] ?? $t['transaction_id'] ?? $t['order_id'] ?? '');
            if ($conversionId === '') continue;

            $statusLower = strtolower((string) ($t['status'] ?? '0'));
            $status = match($statusLower) {
                '1', 'approved', 'success', 'disbursed' => 'approved',
                '2', 'rejected', 'cancelled' => 'rejected',
                default => 'pending',
            };

            $campaignMeta = strtolower(implode(' ', array_filter([
                $t['merchant'] ?? null,
                $t['campaign_name'] ?? null,
                $t['utm_campaign'] ?? null,
                $t['product_id'] ?? null,
                $t['click_url'] ?? null,
            ])));
            [$partner, $normalizedCampaign] = match(true) {
                str_contains($campaignMeta, 'vpbank') => ['isclix', 'VPBank UPL'],
                str_contains($campaignMeta, 'tinvay'),
                str_contains($campaignMeta, 'vietcredit'),
                str_contains($campaignMeta, 'vcredit') => ['accesstrade', 'Tin Vay'],
                str_contains($campaignMeta, '6949942463850829113'),
                str_contains($campaignMeta, 'shinhan android'),
                str_contains($campaignMeta, 'shinhan_android') => ['accesstrade', 'Shinhan Finance Android'],
                str_contains($campaignMeta, '6949939948611548600'),
                str_contains($campaignMeta, 'shinhan ios'),
                str_contains($campaignMeta, 'shinhan_ios') => ['accesstrade', 'Shinhan Finance iOS'],
                default => [null, null],
            };
            $normalizedOffer = match ($normalizedCampaign) {
                'VPBank UPL' => 'vpbank-upl',
                'Tin Vay' => 'tinvay',
                'Shinhan Finance Android' => 'shinhan-finance-android',
                'Shinhan Finance iOS' => 'shinhan-finance-ios',
                default => null,
            };

            // This report endpoint can contain transactions from other offers.
            // Never guess a campaign: a blank/unknown merchant previously polluted VPBank.
            if ($normalizedCampaign === 'Tin Vay' && $status !== 'rejected') {
                $status = 'disbursed';
            }

            if (! $partner || ! $normalizedCampaign) {
                $ignoredCount++;
                Log::notice('Affiliate sync ignored unknown campaign', [
                    'conversion_id' => $conversionId,
                    'merchant' => $campaignMeta,
                ]);
                continue;
            }

            // AccessTrade emits a temporary TinVay row whose order ID is
            // Base64-padded (for example: "abc==") before the complete row.
            // Do not strip the padding and merge it: only the later, complete
            // partner order ID is eligible for synchronization.
            $partnerOrderId = (string) ($t['transaction_id'] ?? $t['order_id'] ?? $conversionId);
            if ($normalizedCampaign === 'Tin Vay' && TinVayOrderId::isIncomplete($partnerOrderId)) {
                $ignoredCount++;
                Log::info('Affiliate sync ignored incomplete TinVay order', [
                    'conversion_id' => $conversionId,
                    'transaction_id' => $partnerOrderId,
                ]);
                continue;
            }

            $saleAmount = (float) ($t['transaction_value'] ?? ($t['order_value'] ?? ($t['price'] ?? ($t['product_price'] ?? 0))));
            $payout = (float) ($t['pub_commission'] ?? ($t['commission'] ?? 0));

            $partnerSubs = (array) data_get($t, '_extra.sub_params', []);
            $sub1 = trim((string) ($partnerSubs['sub1'] ?? ($t['sub1'] ?? ($t['aff_sub1'] ?? ($t['utm_content'] ?? '')))));
            $sub2 = trim((string) ($partnerSubs['sub2'] ?? ($t['sub2'] ?? ($t['aff_sub2'] ?? ($t['utm_medium'] ?? '')))));
            $sub3 = trim((string) ($partnerSubs['sub3'] ?? ($t['sub3'] ?? ($t['aff_sub3'] ?? ($t['utm_campaign'] ?? '')))));
            $sub4 = trim((string) ($partnerSubs['sub4'] ?? ($t['sub4'] ?? ($t['aff_sub4'] ?? ($t['utm_source'] ?? '')))));

            if (filled($sub2) && is_numeric($sub2)) {
                $lead = \App\Models\Lead::find((int) $sub2);
                if ($lead) {
                    $t['customer_name'] = $lead->lead_name;
                    $t['customer_phone'] = $lead->phone;
                    $t['customer_identity_number'] = is_array($lead->payload) ? ($lead->payload['identity_number'] ?? null) : null;
                    $t['customer_dob'] = is_array($lead->payload) ? ($lead->payload['date_of_birth'] ?? null) : null;
                }
            }

            $user = $sub1 !== '' ? User::query()->where('employee_code', $sub1)->orWhere('username', $sub1)->first() : null;

            $existing = AffiliateConversion::where('partner', $partner)->where('conversion_id', $conversionId)->first();
            if (! $existing && in_array($partner, ['isclix', 'accesstrade'], true)) {
                $partnerConversionId = trim((string) ($t['conversion_id'] ?? ''));
                if ($partnerOrderId !== '' && $partnerConversionId !== '') {
                    $existing = AffiliateConversion::query()
                        ->where('partner', $partner)
                        ->where('conversion_id', $partnerOrderId)
                        ->where('transaction_id', $partnerConversionId)
                        ->first();

                    // A callback may arrive before this authoritative report.
                    // Rekey that row to the API report identity instead of
                    // creating a second conversion for the same VPBank order.
                    if ($existing) {
                        $existing->conversion_id = $conversionId;
                    }
                }
            }
            $oldStatus = $existing ? strtolower((string)$existing->conversion_status) : null;

            $conv = $existing ?: new AffiliateConversion([
                'partner' => $partner,
                'conversion_id' => $conversionId,
            ]);
            $attributes = [
                    'transaction_id' => $t['order_id'] ?? $t['transaction_id'] ?? $conversionId,
                    'offer_id' => $normalizedOffer,
                    'campaign_name' => $normalizedCampaign,
                    'conversion_status' => $status,
                    'conversion_status_code' => (string) ($t['status'] ?? '0'),
                    'sale_amount' => $saleAmount,
                    'publisher_payout' => $payout,
                    'click_time' => isset($t['click_time']) ? Carbon::parse($t['click_time']) : null,
                    'conversion_time' => isset($t['action_time'])
                        ? Carbon::parse($t['action_time'])
                        : (isset($t['trans_time'])
                            ? Carbon::parse($t['trans_time'])
                            : (isset($t['transaction_time']) ? Carbon::parse($t['transaction_time']) : null)),
                    'conversion_modified_time' => isset($t['update_time']) ? Carbon::parse($t['update_time']) : null,
                    'conversion_status_updated_time' => isset($t['update_time']) ? Carbon::parse($t['update_time']) : null,
                    'product_name' => $t['product_name'] ?? ($t['campaign_name'] ?? $normalizedCampaign),
                    'product_category' => $normalizedCampaign === 'Tin Vay' ? 'High' : ($t['product_category'] ?? ($t['category_name'] ?? null)),
                    'aff_sub1' => $sub1 ?: null,
                    'aff_sub2' => $sub2 ?: null,
                    'aff_sub3' => $sub3 ?: null,
                    'aff_sub4' => $sub4 ?: null,
                    'created_by_id' => $user?->id ?: ($existing?->created_by_id),
            ];

            // MySQL normalizes JSON object key order. Reassigning the same
            // payload makes Eloquent see a false dirty value on every poll.
            if (! $existing || $existing->raw_payload != $t) {
                $attributes['raw_payload'] = $t;
            }

            $conv->fill($attributes);

            // Do not touch updated_at or emit activity when the partner payload is unchanged.
            if ($conv->exists && ! $conv->isDirty()) {
                $unchangedCount++;
                continue;
            }

            $conv->save();

            // Chỉ bắn thông báo khi LÀ ĐƠN MỚI TINH hoặc CÓ THAY ĐỔI TRẠNG THÁI THỰC TẾ
            if (! $existing || ($oldStatus !== strtolower($status))) {
                AffiliateConversionNotificationSender::changed($conv, $oldStatus);
            }

            $syncedCount++;
        }

        $this->info("Synchronized {$syncedCount}; unchanged {$unchangedCount}; ignored {$ignoredCount}.");
        return self::SUCCESS;
    }
}
