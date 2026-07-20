<?php

namespace App\Support\Notifications;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Application;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Throwable;

class LeadNotificationSender
{
    public static function leadCreated(Lead $lead): void
    {
        self::sendToRecipients(
            lead: $lead,
            eventLabel: 'Lead mới',
            actorLabel: 'Người tạo',
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
            eventLabel: 'Cập nhật Lead',
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
            eventLabel: 'Chuyển lead sang Dự án thành công',
            actorLabel: 'Người xử lý',
            status: 'success',
            icon: Heroicon::OutlinedCheckCircle,
            extraLine: $application?->application_code ? 'Mã hồ sơ: '.$application->application_code : null,
            occurredAt: now()->format('H:i d/m/Y'),
        );
    }

    private static function sendToRecipients(Lead $lead, string $eventLabel, string $actorLabel, string $status, Heroicon $icon, ?string $extraLine, string $occurredAt): void
    {
        try {
            $lead->loadMissing(['salesProject', 'createdBy', 'assignedSale', 'teamLeader', 'am', 'zd']);
            $recipients = self::recipients($lead);

            if ($recipients->isEmpty()) {
                return;
            }

            $title = self::title($lead);
            $actor = auth()->user()?->name ?: $lead->createdBy?->name ?: 'Hệ thống';
            $url = LeadResource::getUrl('view', ['record' => $lead]);
            $body = self::body($eventLabel, $actorLabel, $actor, $occurredAt, $extraLine);

            $recipients->each(function (User $recipient) use ($title, $body, $occurredAt, $icon, $status, $url): void {
                $notification = Notification::make()
                    ->title($title)
                    ->body($body)
                    ->date($occurredAt)
                    ->icon($icon)
                    ->{$status}()
                    ->actions([
                        Action::make('openLead')
                            ->label('Mở Lead')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->button()
                            ->markAsRead()
                            ->url($url),
                    ]);

                $recipient->notifyNow($notification->toDatabase());
                DatabaseNotificationsSent::dispatch($recipient);
            });
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
        ])->filter()->map(fn (mixed $id): int => (int) $id);

        $adminIds = User::role('Admin')->pluck('id');

        return User::query()
            ->whereIn('id', $directIds->merge($adminIds)->unique()->values())
            ->get();
    }

    private static function title(Lead $lead): string
    {
        return implode(' - ', array_filter([
            'Lead',
            $lead->salesProject?->name ?: 'Dự án',
            $lead->lead_code,
            $lead->lead_name,
        ], fn (?string $value): bool => filled($value)));
    }

    private static function body(string $eventLabel, string $actorLabel, string $actor, string $occurredAt, ?string $extraLine): HtmlString
    {
        $lines = [
            '<span class="crm-notification-category" data-category="ho-so">Hồ sơ</span>',
            '<strong>'.e($eventLabel).'</strong>',
            'Thời gian: '.e($occurredAt),
            e($actorLabel).': '.e($actor),
        ];

        if (filled($extraLine)) {
            $lines[] = e($extraLine);
        }

        return new HtmlString(implode('<br>', $lines));
    }
}
