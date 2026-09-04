<?php

namespace App\Support\Notifications;

use App\Jobs\SendWebPushNotification;
use App\Models\AffiliateConversion;
use App\Models\Lead;
use App\Models\User;
use App\Support\AffiliateConversionStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AffiliateConversionNotificationSender
{
    public static function changed(AffiliateConversion $conversion, ?string $previousStatus = null): void
    {
        try {
            $conversionId = $conversion->conversion_id ?: ('CONV-' . $conversion->id);
            $transactionId = ($conversion->transaction_id && $conversion->transaction_id !== '-') ? $conversion->transaction_id : $conversionId;
            $rawStatus = (string) ($conversion->conversion_status ?? 'pending');
            $statusLower = strtolower($rawStatus);

            $phase = AffiliateConversionStatus::phase($statusLower, $conversion->sale_amount, $conversion->campaign_name);
            $status = AffiliateConversionStatus::label($statusLower, $conversion->sale_amount, $conversion->campaign_name);
            $isDisbursed = $phase === AffiliateConversionStatus::DISBURSED;
            $isApprovedWaiting = $phase === AffiliateConversionStatus::APPROVED_WAITING_DISBURSEMENT;
            $isRejected = $phase === AffiliateConversionStatus::REJECTED;

            // Chuẩn hóa tên dự án hiển thị trong thông báo Affiliate Portal.
            $searchMeta = strtolower((string)($conversion->campaign_name . $conversion->partner . $conversion->offer_id . $conversion->landing_page . $conversion->conversion_id));
            $campaignName = match (true) {
                str_contains($searchMeta, 'vpbank') => 'VPBank UPL',
                str_contains($searchMeta, 'shinhan') && str_contains($searchMeta, 'android') => 'Shinhan Finance Android',
                str_contains($searchMeta, 'shinhan') && str_contains($searchMeta, 'ios') => 'Shinhan Finance iOS',
                str_contains($searchMeta, 'shinhan') => 'Shinhan Finance',
                str_contains($searchMeta, 'shb') || strtolower((string)$conversion->partner) === 'hyperlead' => 'SHB Finance',
                str_contains($searchMeta, 'tinvay') || str_contains($searchMeta, 'vietcredit') => 'Tin Vay',
                default => $conversion->campaign_name ?: 'SHB Finance',
            };

            // CHỐNG SPAM: Mỗi mã đơn ở mỗi trạng thái chỉ gửi duy nhất 1 lần
            $dedupKey = "notif_sent_{$conversion->partner}_{$conversionId}_{$phase}";
            if (Cache::has($dedupKey)) {
                return;
            }
            Cache::put($dedupKey, true, now()->addDays(15));

            $saleUser = $conversion->createdBy;
            if (! $saleUser && filled($conversion->aff_sub1)) {
                $code = trim((string) $conversion->aff_sub1);
                $saleUser = User::query()
                    ->where('employee_code', $code)
                    ->orWhere('username', $code)
                    ->orWhere('id', is_numeric($code) ? (int)$code : 0)
                    ->first();
            }

            $userDisplay = $saleUser ? $saleUser->name : 'Hệ thống';

            $recipients = self::resolveRecipients($conversion);
            if ($recipients->isEmpty()) {
                return;
            }

            $rawPayload = (array) ($conversion->raw_payload ?? []);
            $customerName = $rawPayload['customer_name'] ?? null;
            $customerPhone = $rawPayload['customer_phone'] ?? null;

            if ((!$customerName || !$customerPhone) && is_numeric($conversion->aff_sub2)) {
                $lead = Lead::find((int) $conversion->aff_sub2);
                if ($lead) {
                    $customerName = $customerName ?: $lead->lead_name;
                    $customerPhone = $customerPhone ?: $lead->phone;
                }
            }

            $customerDisplay = $customerName ?: 'Khách hàng';

            // Số tiền giải ngân cho hồ sơ thành công
            $saleAmount = (float) ($conversion->sale_amount ?? ($rawPayload['sale_amount'] ?? ($rawPayload['amount'] ?? 0)));
            $amountFormatted = $saleAmount > 0 ? (number_format($saleAmount, 0, ',', '.') . ' VNĐ') : null;

            // Tiêu đề chuẩn CaseID
            $caseId = $transactionId;
            if ($isDisbursed) {
                if ($amountFormatted) {
                    $title = "🎉 [CaseID: {$caseId}] Giải ngân {$amountFormatted} - {$customerDisplay} ({$campaignName})";
                } else {
                    $title = "🎉 [CaseID: {$caseId}] Giải ngân thành công - {$customerDisplay} ({$campaignName})";
                }
            } elseif ($isApprovedWaiting) {
                $title = "✅ [CaseID: {$caseId}] Đã duyệt - Chờ giải ngân - {$customerDisplay} ({$campaignName})";
            } elseif ($isRejected) {
                $title = "❌ [CaseID: {$caseId}] Từ chối hồ sơ - {$customerDisplay} ({$campaignName})";
            } else {
                $title = "⏳ [CaseID: {$caseId}] Hồ sơ đang thẩm định - {$customerDisplay} ({$campaignName})";
            }

            $bodyLines = [
                "👤 Khách hàng: {$customerDisplay}" . ($customerPhone ? " ({$customerPhone})" : ''),
                "📋 Dự án: {$campaignName}",
                "🔢 Mã GD: {$caseId}",
                "📊 Trạng thái: {$status}",
            ];

            if ($isDisbursed) {
                $bodyLines[] = "💰 Số tiền giải ngân: " . ($amountFormatted ?: 'Đang cập nhật đối soát');
            } elseif ($isApprovedWaiting) {
                $bodyLines[] = "💰 Số tiền được duyệt: " . ($amountFormatted ?: 'Đang cập nhật');
            }

            $bodyLines[] = "👨‍💼 Nhân sự phụ trách: {$userDisplay}";
            $bodyLines[] = "⏰ Thời gian: " . now()->format('H:i d/m/Y');

            $body = implode("\n", $bodyLines);
            $url = 'https://apps2.3rdvn.io.vn/applications/affiliate';

            // 1. Lưu Database Notifications cho từng người nhận
            $recipients->each(function (User $recipient) use ($title, $body, $conversionId, $phase, $url): void {
                try {
                    DB::table('notifications')->insert([
                        'id' => (string) Str::uuid(),
                        'type' => 'Filament\Notifications\DatabaseNotification',
                        'notifiable_type' => User::class,
                        'notifiable_id' => $recipient->id,
                        'data' => json_encode([
                            'title' => $title,
                            'body' => $body,
                            'conversion_id' => $conversionId,
                            'status' => $phase,
                            'actions' => [
                                [
                                    'name' => 'open',
                                    'label' => 'Xem chi tiết',
                                    'url' => $url,
                                    'button' => true,
                                    'markAsRead' => true,
                                ],
                            ],
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
            });

            // 2. Dispatch CRM Web Push
            try {
                SendWebPushNotification::dispatch($recipients->modelKeys(), [
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                    'tag' => 'affiliate-'.$conversionId.'-'.$phase,
                ]);
            } catch (Throwable $e) {}

            // 3. Broadcast trực tiếp sang Affiliate Portal Node Push Server (Port 3070)
            try {
                $recipientUserIds = $recipients->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
                $recipientUserCodes = $recipients->pluck('employee_code')->filter()->map(fn ($c): string => strtoupper(trim((string) $c)))->unique()->values()->all();

                Http::timeout(3)->post('http://127.0.0.1:3070/api/internal/push-broadcast', [
                    'recipient_ids' => $recipientUserIds,
                    'recipient_codes' => $recipientUserCodes,
                    'title' => $title,
                    'body' => $body,
                    'url' => '/?tab=reports',
                    'tag' => 'aff-portal-'.$conversionId.'-'.$phase,
                ]);
            } catch (Throwable $e) {}

        } catch (Throwable $exception) {
            Log::error("[Affiliate Notification Error] " . $exception->getMessage(), [
                'exception' => $exception,
            ]);
        }
    }

    private static function resolveRecipients(AffiliateConversion $conversion): Collection
    {
        $creator = $conversion->createdBy;
        if (! $creator && filled($conversion->aff_sub1)) {
            $code = trim((string) $conversion->aff_sub1);
            $creator = User::query()
                ->where('employee_code', $code)
                ->orWhere('username', $code)
                ->first();
        }

        $userIds = collect();
        if ($creator) {
            $userIds->push(
                $creator->id,
                $creator->team_leader_id,
                $creator->am_id,
                $creator->zd_id,
                $creator->courier_manager_id
            );
        }

        // Add Super Admin / Directors
        $adminIds = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Super Admin', 'Director', 'General Manager', 'Manager']))
            ->orWhere('employee_code', 'RD260001')
            ->orWhere('id', 1)
            ->pluck('id');

        $userIds = $userIds->merge($adminIds)->filter()->unique()->values();

        return User::query()->whereIn('id', $userIds)->get();
    }
}
