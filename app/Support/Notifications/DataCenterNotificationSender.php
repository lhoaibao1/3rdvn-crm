<?php

namespace App\Support\Notifications;

use App\Filament\Resources\DataCenterLeads\DataCenterLeadResource;
use App\Models\DataCenterLead;
use App\Models\User;
use App\Support\DataCenter\DataCenterStatus;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Throwable;

class DataCenterNotificationSender
{
    public static function imported(User $assignee, User $actor, int $count): void
    {
        if ($count < 1) {
            return;
        }

        try {
            $body = new HtmlString(implode('<br>', [
                '<span class="crm-notification-category" data-category="ho-so">Hồ sơ</span>',
                '<strong>Tổng số Lead được giao: '.e((string) $count).'</strong>',
                'Người chuyển giao: '.e($actor->name ?: $actor->uid ?: 'Hệ thống'),
                'Thời gian: '.e(now()->format('H:i d/m/Y')),
            ]));

            self::deliver(
                self::assigneeAndAdmins($assignee),
                'Lead Refernal - Bạn được giao Lead để xử lý',
                $body,
                Heroicon::OutlinedUserPlus,
                'info',
                DataCenterLeadResource::getUrl('index'),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public static function created(DataCenterLead $record): void
    {
        self::send($record, 'Lead mới được phân bổ', 'warning', Heroicon::OutlinedCircleStack);
    }

    public static function assigned(DataCenterLead $record): void
    {
        self::send($record, 'Bạn được giao Lead để xử lý', 'info', Heroicon::OutlinedUserPlus);
    }

    public static function resultUpdated(DataCenterLead $record): void
    {
        self::send(
            $record,
            'Trạng thái Lead: '.DataCenterStatus::label($record->status),
            DataCenterStatus::color($record->status),
            Heroicon::OutlinedPhone,
        );
    }

    public static function converted(DataCenterLead $record, int $count): void
    {
        self::send(
            $record,
            $count === 1 ? 'Đã chuyển sang 1 dự án' : 'Đã chuyển sang 2 dự án',
            'success',
            Heroicon::OutlinedCheckCircle,
        );
    }

    private static function send(DataCenterLead $record, string $event, string $tone, Heroicon $icon): void
    {
        try {
            $record->loadMissing(['assignedUser']);
            $recipients = self::recipients($record);

            if ($recipients->isEmpty()) {
                return;
            }

            $actor = auth()->user()?->name ?: 'Hệ thống';
            $url = DataCenterLeadResource::getUrl('view', ['record' => $record]);
            $title = implode(' - ', array_filter(['Lead Referral', $record->referral_code, $record->customer_name]));
            $body = new HtmlString(implode('<br>', [
                '<span class="crm-notification-category" data-category="ho-so">Hồ sơ</span>',
                '<strong>'.e($event).'</strong>',
                'Thời gian: '.e(now()->format('H:i d/m/Y')),
                'Người thao tác: '.e($actor),
            ]));

            self::deliver($recipients, $title, $body, $icon, $tone, $url);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function deliver(
        Collection $recipients,
        string $title,
        HtmlString $body,
        Heroicon $icon,
        string $tone,
        string $url,
    ): void {
        $recipients->each(function (User $recipient) use ($title, $body, $icon, $tone, $url): void {
            $notification = Notification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->{$tone}()
                ->actions([
                    Action::make('openDataCenter')
                        ->label('Mở Lead Referral')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->button()
                        ->markAsRead()
                        ->url($url),
                ]);

            $recipient->notifyNow($notification->toDatabase());
            DatabaseNotificationsSent::dispatch($recipient);
        });
    }

    private static function recipients(DataCenterLead $record): Collection
    {
        $ids = collect([$record->assigned_user_id])
            ->filter()
            ->merge(User::role('Admin')->pluck('id'))
            ->unique();

        return User::query()->whereIn('id', $ids)->get();
    }

    private static function assigneeAndAdmins(User $assignee): Collection
    {
        $ids = collect([$assignee->getKey()])
            ->merge(User::role('Admin')->pluck('id'))
            ->unique();

        return User::query()->whereIn('id', $ids)->get();
    }
}
