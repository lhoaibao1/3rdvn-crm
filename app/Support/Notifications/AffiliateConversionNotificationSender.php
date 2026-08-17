<?php

namespace App\Support\Notifications;

use App\Jobs\SendWebPushNotification;
use App\Models\AffiliateConversion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
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

            $campaign = $conversion->campaign_name ?: $conversion->offer_id ?: 'Affiliate';
            $status = $conversion->conversion_status ?: 'Mới ghi nhận';
            $title = $campaign.' - Cập nhật kết quả';
            $body = 'Mã chuyển đổi: '.$conversion->conversion_id
                .' · Trạng thái: '.$status
                .' · Mã nhân viên: '.($conversion->aff_sub1 ?: '-')
                .' · Mã giao dịch: '.($conversion->transaction_id ?: '-');
            $url = url('/applications/affiliate');

            $recipients->each(function (User $recipient) use ($title, $body, $url): void {
                $notification = Notification::make()
                    ->title($title)->body($body)
                    ->icon(Heroicon::OutlinedCursorArrowRays)->info()
                    ->actions([Action::make('openAffiliate')->label('Mở Affiliate')->markAsRead()->url($url)]);
                $recipient->notifyNow($notification->toDatabase());
                DatabaseNotificationsSent::dispatch($recipient);
            });

            SendWebPushNotification::dispatch($recipients->modelKeys(), [
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'tag' => 'affiliate-'.$conversion->partner.'-'.$conversion->conversion_id.'-'.$status,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
