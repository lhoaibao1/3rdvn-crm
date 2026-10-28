<?php

namespace App\Console\Commands;

use App\Models\AffiliateConversion;
use App\Models\Lead;
use App\Support\Affiliate\UpsertAffiliateConversion;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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
        $failedPages = 0;
        $consecutiveFailures = 0;
        for ($page = 1; $page <= 100; $page++) {
            try {
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
                $consecutiveFailures = 0;
            } catch (Throwable $e) {
                // Targeted refreshes must fail loudly so the queued command can retry.
                if (isset($query['transaction_id'])) {
                    throw $e;
                }

                $failedPages++;
                $consecutiveFailures++;
                Log::warning('HyperLead report page sync failed', [
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Bỏ qua trang {$page} do API tạm thời không phản hồi.");

                // One failed middle page must not prevent newer pages being imported,
                // but stop quickly during a genuine partner outage.
                if ($consecutiveFailures >= 2) {
                    break;
                }
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row['transaction_id'])) {
                    continue;
                }
                $row['conversion_id'] = (string) ($row['conversion_id']
                    ?? (($row['offer_id'] ?? '').$row['transaction_id']));

                if (! $this->hasValidSfinanceLandingAttribution($row)) {
                    Log::warning('Skipped new SFinance conversion without verified landing submission', [
                        'conversion_id' => $row['conversion_id'],
                        'transaction_id' => $row['transaction_id'] ?? null,
                        'aff_sub1' => $row['aff_sub1'] ?? null,
                    ]);
                    continue;
                }

                $upsert->handle($row, 'hyperlead');
                $count++;
            }
            if (count($rows) < 100) {
                break;
            }
        }
        $suffix = $failedPages > 0 ? " ({$failedPages} trang tạm lỗi)" : '';
        $this->info("Đã đồng bộ {$count} chuyển đổi HyperLead{$suffix}.");
        return self::SUCCESS;
    }

    /**
     * SFinance links must pass through our landing form. Existing historical
     * conversions remain refreshable, while every new conversion must carry the
     * lead id (sub2) and the server-generated landing token (sub3).
     */
    private function hasValidSfinanceLandingAttribution(array $row): bool
    {
        $identity = strtolower((string) (($row['offer_id'] ?? '').' '.($row['conversion_id'] ?? '')));
        if (! str_contains($identity, 'shbfinance')) {
            return true;
        }

        $conversionId = trim((string) ($row['conversion_id'] ?? ''));
        if ($conversionId !== '' && AffiliateConversion::query()
            ->where('partner', 'hyperlead')
            ->where('conversion_id', $conversionId)
            ->exists()) {
            return true;
        }

        $leadId = trim((string) ($row['aff_sub2'] ?? ''));
        $token = trim((string) ($row['aff_sub3'] ?? ''));
        if ($leadId === '' || ! ctype_digit($leadId) || $token === '') {
            return false;
        }

        $lead = Lead::find((int) $leadId);
        if (! $lead || $lead->source !== 'affiliate_landing') {
            return false;
        }

        $payload = (array) ($lead->payload ?? []);
        $expectedToken = trim((string) ($payload['affiliate_tracking_token'] ?? ''));
        $expectedCampaign = strtolower((string) ($payload['campaign_slug'] ?? ''));
        $expectedEmployee = trim((string) ($payload['aff_sub1'] ?? ''));
        $receivedEmployee = trim((string) ($row['aff_sub1'] ?? ''));

        return in_array($expectedCampaign, ['shb-finance', 'shbfinance'], true)
            && $expectedToken !== ''
            && hash_equals($expectedToken, $token)
            && $expectedEmployee !== ''
            && hash_equals($expectedEmployee, $receivedEmployee);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(20)->connectTimeout(5)->retry(2, 500);
    }
}
