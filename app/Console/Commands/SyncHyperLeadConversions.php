<?php

namespace App\Console\Commands;

use App\Support\Affiliate\UpsertAffiliateConversion;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncHyperLeadConversions extends Command
{
    protected $signature = 'affiliate:sync-hyperlead {--transaction=}';
    protected $description = 'Đồng bộ toàn bộ dữ liệu chuyển đổi từ API report HyperLead';

    public function handle(UpsertAffiliateConversion $upsert): int
    {
        $publisherId = trim((string) config('services.affiliate.publisher_id'));
        $token = trim((string) config('services.affiliate.api_token'));
        $baseUrl = rtrim((string) (config('services.affiliate.api_base_url') ?: 'https://publisher-api.riofintech.net'), '/');
        if ($publisherId === '' || $token === '') {
            $this->error('Thiếu AFFILIATE_PUBLISHER_ID hoặc AFFILIATE_API_TOKEN.');
            return self::FAILURE;
        }

        $query = [
            'publisher_id' => $publisherId,
            'token' => $token,
            'limit' => 100,
        ];
        if ($transaction = trim((string) $this->option('transaction'))) {
            $query['transaction_id'] = $transaction;
        }

        $count = 0;
        for ($page = 1; $page <= 100; $page++) {
            $response = $this->client()->get($baseUrl.'/v1/conversions', $query + ['page' => $page]);
            $response->throw();
            $json = $response->json();
            if ((int) ($json['status'] ?? 0) !== 1) {
                throw new RuntimeException((string) ($json['message'] ?? 'HyperLead API trả về lỗi.'));
            }
            $rows = $json['data'] ?? [];
            if (! is_array($rows)) {
                throw new RuntimeException('HyperLead API trả về data không hợp lệ.');
            }
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['transaction_id'])) {
                    continue;
                }
                $row['conversion_id'] = (string) ($row['conversion_id']
                    ?? (($row['offer_id'] ?? '').$row['transaction_id']));
                $upsert->handle($row, 'hyperlead');
                $count++;
            }
            if (count($rows) < 100) {
                break;
            }
        }
        $this->info("Đã đồng bộ {$count} chuyển đổi HyperLead.");
        return self::SUCCESS;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(20)->connectTimeout(5)->retry(2, 500);
    }
}
