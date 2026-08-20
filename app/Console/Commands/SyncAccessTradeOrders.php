<?php

namespace App\Console\Commands;

use App\Models\AffiliateConversion;
use App\Models\User;
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
        $until = Carbon::now()->format('Y-m-d');
        $apiKey = '4PIctagU6THxrsB-aipcKXZyFGn4zmig';

        $this->info("Fetching AccessTrade transactions from {$since} to {$until}...");

        $url = "https://api.accesstrade.vn/v1/transactions?since={$since}&until={$until}&limit=500";
        $response = Http::withHeaders([
            'Authorization' => "Token {$apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(30)->get($url);

        if (! $response->successful()) {
            $this->error("AccessTrade API Error: " . $response->status() . " - " . $response->body());
            return self::FAILURE;
        }

        $json = $response->json();
        $transactions = $json['data'] ?? [];
        $this->info("Found " . count($transactions) . " transactions.");

        $syncedCount = 0;
        foreach ($transactions as $t) {
            $conversionId = (string) ($t['id'] ?? $t['transaction_id'] ?? $t['order_id'] ?? '');
            if ($conversionId === '') continue;

            $statusLower = strtolower((string) ($t['status'] ?? '0'));
            $status = match($statusLower) {
                '1', 'approved', 'success' => 'approved',
                '2', 'rejected', 'cancelled' => 'rejected',
                default => 'pending',
            };

            $sub1 = trim((string) ($t['utm_content'] ?? ($t['sub1'] ?? ($t['aff_sub1'] ?? ''))));
            $sub2 = trim((string) ($t['utm_medium'] ?? ($t['sub2'] ?? ($t['aff_sub2'] ?? ''))));
            $sub3 = trim((string) ($t['utm_campaign'] ?? ($t['sub3'] ?? ($t['aff_sub3'] ?? ''))));
            $sub4 = trim((string) ($t['utm_source'] ?? ($t['sub4'] ?? ($t['aff_sub4'] ?? ''))));

            $user = $sub1 !== '' ? User::query()->where('employee_code', $sub1)->orWhere('username', $sub1)->first() : null;

            AffiliateConversion::updateOrCreate(
                [
                    'partner' => 'accesstrade',
                    'conversion_id' => $conversionId,
                ],
                [
                    'transaction_id' => $t['order_id'] ?? $t['transaction_id'] ?? $conversionId,
                    'campaign_name' => $t['campaign_name'] ?? $t['merchant'] ?? 'AccessTrade Campaign',
                    'conversion_status' => $status,
                    'conversion_status_code' => (string) ($t['status'] ?? '0'),
                    'sale_amount' => (float) ($t['order_value'] ?? ($t['price'] ?? 0)),
                    'publisher_payout' => (float) ($t['pub_commission'] ?? ($t['commission'] ?? 0)),
                    'click_time' => isset($t['click_time']) ? Carbon::parse($t['click_time']) : null,
                    'conversion_time' => isset($t['action_time']) ? Carbon::parse($t['action_time']) : (isset($t['trans_time']) ? Carbon::parse($t['trans_time']) : now()),
                    'product_name' => $t['product_name'] ?? ($t['campaign_name'] ?? null),
                    'aff_sub1' => $sub1 ?: null,
                    'aff_sub2' => $sub2 ?: null,
                    'aff_sub3' => $sub3 ?: null,
                    'aff_sub4' => $sub4 ?: null,
                    'created_by_id' => $user?->id,
                    'raw_payload' => $t,
                ]
            );
            $syncedCount++;
        }

        $this->info("Successfully synchronized {$syncedCount} AccessTrade transactions!");
        return self::SUCCESS;
    }
}
