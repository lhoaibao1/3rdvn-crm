<?php

namespace App\Support\Notifications;

use App\Filament\Pages\Mail as MailPage;
use App\Models\User;
use App\Services\StalwartMailService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class MailNotificationSender
{
    public static function synchronize(User $user, StalwartMailService $mail): int
    {
        $created = 0;
        $role = 'inbox';
        $messages = $mail->recentMessages($user, $role);
        $initializedKey = "crm:mail-notifications:initialized:{$user->getKey()}:{$role}";

        if (! Cache::has($initializedKey)) {
            foreach ($messages as $message) {
                Cache::forever(self::messageKey($user, $role, $message), true);
            }

            Cache::forever($initializedKey, true);

            return 0;
        }

        foreach (array_reverse($messages) as $message) {
            if (! Cache::add(self::messageKey($user, $role, $message), true, now()->addYear())) {
                continue;
            }

            if (self::isDeliveryFailure($message)) {
                continue;
            }

            self::send($user, $message);
            $created++;
        }

        return $created;
    }

    private static function send(User $user, array $message): void
    {
        $subject = trim((string) ($message['subject'] ?? '')) ?: '(Không có tiêu đề)';
        $occurredAt = CarbonImmutable::parse($message['receivedAt'] ?? $message['sentAt'] ?? now())
            ->setTimezone(config('app.timezone'));
        $contact = self::contacts($message['from'] ?? []);
        $sender = self::contactName($message['from'] ?? []) ?: 'Mail';
        $preview = Str::limit(trim((string) ($message['preview'] ?? '')), 180);

        $bodyLines = [
            '<span class="crm-notification-category" data-category="mail">Mail</span>',
            '<strong>Có mail mới</strong>',
            'Người gửi: '.e($contact ?: '-'),
            'Thời gian: '.$occurredAt->format('H:i d/m/Y'),
        ];

        if ($preview !== '') {
            $bodyLines[] = e($preview);
        }

        $notification = Notification::make()
            ->title($sender.'. '.$subject)
            ->body(new HtmlString(implode('<br>', $bodyLines)))
            ->date($occurredAt->format('H:i d/m/Y'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->info()
            ->actions([
                Action::make('openMail')
                    ->label('Mở Mail')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->button()
                    ->markAsRead()
                    ->url(MailPage::getUrl()),
            ]);

        $user->notifyNow($notification->toDatabase());
        DatabaseNotificationsSent::dispatch($user);
    }

    private static function messageKey(User $user, string $role, array $message): string
    {
        return 'crm:mail-notifications:message:'.hash('sha256', implode('|', [
            $user->getKey(),
            $role,
            (string) ($message['id'] ?? ''),
        ]));
    }

    private static function contactName(array $contacts): string
    {
        $contact = collect($contacts)->first();

        if (! is_array($contact)) {
            return '';
        }

        return trim((string) ($contact['name'] ?? ''))
            ?: trim((string) ($contact['email'] ?? ''));
    }

    private static function contacts(array $contacts): string
    {
        return collect($contacts)
            ->map(function (array $contact): string {
                $email = trim((string) ($contact['email'] ?? ''));
                $name = trim((string) ($contact['name'] ?? ''));

                return $name !== '' && $name !== $email ? "{$name} <{$email}>" : $email;
            })
            ->filter()
            ->implode(', ');
    }

    private static function isDeliveryFailure(array $message): bool
    {
        $subject = Str::lower((string) ($message['subject'] ?? ''));
        $sender = Str::lower(self::contacts($message['from'] ?? []));

        return Str::contains($subject, [
            'failed to deliver',
            'delivery status notification',
            'undeliverable',
            'undelivered mail',
            'mail delivery failed',
            'không gửi được',
            'gửi thư thất bại',
        ])
            || Str::contains($sender, ['mailer-daemon', 'postmaster']);
    }
}
