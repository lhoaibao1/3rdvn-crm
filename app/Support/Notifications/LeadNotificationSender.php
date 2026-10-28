<?php

namespace App\Support\Notifications;

use App\Filament\Resources\Leads\LeadResource;
use App\Jobs\SendWebPushNotification;
use App\Models\Application;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LeadNotificationSender
{
    public static function leadCreated(Lead $lead): void
    {
        self::sendToRecipients(
            lead: $lead,
            eventLabel: 'Tiếp nhận hồ sơ',
            actorLabel: 'Nhân sự phụ trách',
            status: 'warning',
            icon: Heroicon::OutlinedBellAlert,
            extraLine: null,
            occurredAt: $lead->created_at?->format('H:i d/m/Y') ?: now()->format('H:i d/m/Y'),
        );
    }

    public static function leadUpdated(Lead $lead, array $changes = []): void
    {
        $status = data_get($changes, 'status.new');

        self::sendToRecipients(
            lead: $lead,
            eventLabel: 'Cập nhật hồ sơ',
            actorLabel: 'Người cập nhật',
            status: 'info',
            icon: Heroicon::OutlinedPencilSquare,
            extraLine: filled($status) ? 'Trạng thái: '.$status : null,
            occurredAt: now()->format('H:i d/m/Y'),
        );
    }

    public static function leadConverted(Lead $lead, ?Application $application = null): void
    {
        self::sendToRecipients(
            lead: $lead,
            eventLabel: 'Chuyển sang Dự án thành công',
            actorLabel: 'Người xử lý',
            status: 'success',
            icon: Heroicon::OutlinedCheckCircle,
            extraLine: $application?->application_code ? 'Mã GD: '.$application->application_code : null,
            occurredAt: now()->format('H:i d/m/Y'),
        );
    }

    private static function sendToRecipients(Lead $lead, string $eventLabel, string $actorLabel, string $status, Heroicon $icon, ?string $extraLine, string $occurredAt): void
    {
        try {
            $lead->loadMissing(['salesProject', 'createdBy', 'assignedSale.courierManager', 'teamLeader', 'am', 'zd']);
            $recipients = self::recipients($lead);

            if ($recipients->isEmpty()) {
                return;
            }

            $payload = (array) ($lead->payload ?? []);
            $isAffiliate = $lead->source === 'affiliate_landing' || !empty($payload['campaign_name']);
            $campaignName = $payload['campaign_name'] ?? ($lead->salesProject?->name ?: 'Dự án');

            // Chuẩn hóa tên chiến dịch
            if (str_contains(strtolower($campaignName), 'shb') || str_contains(strtolower($campaignName), 'hyperlead')) {
                $campaignName = 'SHB Finance';
            } elseif (str_contains(strtolower($campaignName), 'vpbank')) {
                $campaignName = 'VPBank UPL';
            }

            $customerName = $lead->lead_name ?: 'Khách hàng';
            $customerPhone = $lead->phone ?: '-';
            $actor = auth()->user()?->name ?: $lead->createdBy?->name ?: $lead->assignedSale?->name ?: 'Hệ thống';
            $caseId = $lead->lead_code ?: ('LD-' . $lead->id);

            // Tiêu đề chuẩn CaseID
            $title = "🔔 [CaseID: {$caseId}] {$customerName} - {$campaignName}";

            // Nội dung thông báo thuần văn bản (Mã GD)
            $bodyLines = [
                "👤 Khách hàng: {$customerName}",
                "📞 Số điện thoại: {$customerPhone}",
                "📋 Dự án: {$campaignName}",
                "🔢 Mã GD: {$caseId}",
                "👨‍💼 {$actorLabel}: {$actor}",
                "⏰ Thời gian: {$occurredAt}",
            ];

            if (filled($extraLine)) {
                $bodyLines[] = "📌 {$extraLine}";
            }

            $bodyText = implode("\n", $bodyLines);

            // Đường dẫn URL chuẩn CRM Apps2
            $url = $isAffiliate
                ? 'https://apps2.3rdvn.io.vn/applications/affiliate'
                : 'https://apps2.3rdvn.io.vn/leads/' . $lead->id;

            $recipients->each(function (User $recipient) use ($title, $bodyText, $occurredAt, $icon, $status, $url): void {
                $notification = Notification::make()
                    ->title($title)
                    ->body($bodyText)
                    ->date($occurredAt)
                    ->icon($icon)
                    ->{$status}()
                    ->actions([
                        Action::make('openLead')
                            ->label('Xem chi tiết')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->button()
                            ->markAsRead()
                            ->url($url),
                    ]);

                $recipient->notifyNow($notification->toDatabase());
                DatabaseNotificationsSent::dispatch($recipient);
            });

            // Gửi WebPush tới máy tính / thiết bị di động
            try {
                SendWebPushNotification::dispatch($recipients->modelKeys(), [
                    'title' => $title,
                    'body' => $bodyText,
                    'icon' => 'https://3rdvn.io.vn/favicon.png',
                    'data' => ['url' => $url],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to dispatch Lead WebPush: ' . $e->getMessage());
            }

            // Bắn tín hiệu socket sang Cổng Affiliate (Port 3070)
            try {
                Http::timeout(1.5)->post('http://127.0.0.1:3070/api/portal/push-notification', [
                    'user_ids' => $recipients->modelKeys(),
                    'title' => $title,
                    'message' => "Khách hàng {$customerName} ({$customerPhone}) đăng ký dự án {$campaignName}.",
                    'type' => 'lead_created',
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'campaign_name' => $campaignName,
                    'lead_id' => $lead->id,
                    'url' => $url,
                ]);
            } catch (\Throwable $e) {}

        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function recipients(Lead $lead): Collection
    {
        $directIds = collect([
            $lead->created_by_id,
            $lead->assigned_sale_id,
            $lead->team_leader_id,
            $lead->am_id,
            $lead->zd_id,
            $lead->assignedSale?->courier_manager_id,
        ])->filter()->map(fn (mixed $id): int => (int) $id);

        $adminIds = User::role('Admin')->pluck('id');

        return User::query()
            ->whereIn('id', $directIds->merge($adminIds)->unique()->values())
            ->get();
    }
}
