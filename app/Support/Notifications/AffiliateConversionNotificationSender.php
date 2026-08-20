<?php

namespace App\Support\Notifications;

use App\Jobs\SendWebPushNotification;
use App\Models\AffiliateConversion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;
use Throwable;

class AffiliateConversionNotificationSender
{
    public static function changed(AffiliateConversion $conversion): void
    {
        try {
            $conversion->loadMissing('createdBy.team.manager');
            $owner = $conversion->createdBy;
            $recipientIds = collect([
                $conversion->created_by_id,
                $owner?->team_leader_id,
                $owner?->team?->manager_id,
                $owner?->am_id,
                $owner?->zd_id,
                $owner?->courier_manager_id,
            ])->filter()->map(fn (mixed $id): int => (int) $id)
                ->merge(User::role(['Admin', 'Sales Admin'])->pluck('id'))
                ->unique()->values();
            $recipients = User::query()->whereIn('id', $recipientIds)->get();

            if ($recipients->isEmpty()) {
                return;
            }

            $campaign = $conversion->campaign_name ?: ($conversion->offer_id ?: 'Dự án tiếp thị');
            $status = $conversion->conversion_status ?: 'Mới ghi nhận';
            $statusLower = strtolower(trim($status));
            $caseId = $conversion->transaction_id ?: ($conversion->conversion_id ?: '-');

            $isApproved = in_array($statusLower, ['approved', 'phe_duyet', 'phê duyệt', 'disbursed', 'giải ngân', 'giai_ngan', 'success', 'thành công', 'completed', 'paid'], true);
            $isRejected = in_array($statusLower, ['rejected', 'tu_choi', 'từ chối', 'cancelled', 'canceled', 'huy', 'huỷ', 'failed', 'that_bai', 'thất bại', 'trash'], true);

            // 1. Tiêu đề thông báo kèm icon cảm xúc sinh động
            if ($isApproved) {
                $title = '🎉 Chúc mừng, bạn có hồ sơ mới giải ngân';
            } elseif ($isRejected) {
                $title = '❌ Rất tiếc, bạn có hồ sơ thất bại';
            } else {
                $title = '📋 Cập nhật hồ sơ';
            }

            // 2. Nội dung thông báo kèm icon chuẩn hóa, Số tiền duyệt ở TRÊN, User ở DƯỚI
            $sub1 = trim((string) ($conversion->aff_sub1 ?: ($owner?->employee_code ?: '')));
            $userDisplay = $owner ? "{$owner->name} ({$sub1})" : ($sub1 ?: 'Hệ thống');

            $bodyLines = [
                "🏢 Dự án: {$campaign}",
                "🔖 Mã giao dịch/CaseID: {$caseId}",
                "📊 Trạng thái: {$status}",
            ];

            if ($isApproved && $conversion->sale_amount && $conversion->sale_amount > 0) {
                $bodyLines[] = "💰 Số tiền duyệt: " . number_format($conversion->sale_amount, 0, ',', '.') . " đ";
            }

            $bodyLines[] = "👤 User: {$userDisplay}";

            $body = implode("\n", $bodyLines);
            $url = url('/applications/affiliate');

            $recipients->each(function (User $recipient) use ($title, $body, $url): void {
                $notification = Notification::make()
                    ->title($title)->body($body)
                    ->icon(Heroicon::OutlinedCursorArrowRays)->info()
                    ->actions([Action::make('openAffiliate')->label('Mở Affiliate')->markAsRead()->url($url)]);
                $recipient->notifyNow($notification->toDatabase());
                DatabaseNotificationsSent::dispatch($recipient);
            });

            // 1. Dispatch CRM Web Push
            try {
                SendWebPushNotification::dispatch($recipients->modelKeys(), [
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                    'tag' => 'affiliate-'.$conversion->partner.'-'.$conversion->conversion_id.'-'.$status,
                ]);
            } catch (Throwable $e) {}

            // 2. Broadcast directly to 3RD-VN Affiliate Portal Node Push Server (Port 3070)
            try {
                Http::timeout(2)->post('http://127.0.0.1:3070/api/internal/push-broadcast', [
                    'user_code' => $sub1 ?: 'all',
                    'title' => $title,
                    'body' => $body,
                    'icon' => '/static/logo.jpg',
                    'badge' => '/static/logo.jpg',
                    'url' => '/?tab=reports',
                    'tag' => 'aff-' . $conversion->id . '-' . time()
                ]);
            } catch (Throwable $e) {
                // Non-blocking
            }

        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
