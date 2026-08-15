<?php

namespace App\Support\Notifications;

use App\Models\Application;
use App\Models\User;
use App\Support\Applications\ProjectWorkflowConfiguration;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class ApplicationNotificationSender
{
    public static function created(Application $application, ?int $actorId = null): void
    {
        self::send(
            application: $application,
            eventLabel: 'Hồ sơ mới',
            detailLine: 'Trạng thái: '.self::statusLabel($application, $application->status),
            tone: 'warning',
            icon: Heroicon::OutlinedDocumentPlus,
            actorId: $actorId,
        );
    }

    public static function assigned(
        Application $application,
        ?int $previousAssigneeId,
        ?int $assigneeId,
        ?int $actorId = null,
    ): void {
        $userNames = User::query()
            ->whereIn('id', array_values(array_filter([$previousAssigneeId, $assigneeId])))
            ->pluck('name', 'id');

        $previousAssignee = $previousAssigneeId
            ? ($userNames[$previousAssigneeId] ?? 'Người xử lý cũ')
            : 'Chưa phân bổ';
        $assignee = $assigneeId
            ? ($userNames[$assigneeId] ?? 'Người xử lý mới')
            : 'Đã bỏ phân bổ';

        self::send(
            application: $application,
            eventLabel: 'Phân bổ xử lý hồ sơ',
            detailLine: 'Người xử lý: '.$previousAssignee.' → '.$assignee,
            tone: 'info',
            icon: Heroicon::OutlinedUserPlus,
            actorId: $actorId,
            extraRecipientIds: [$previousAssigneeId, $assigneeId],
        );
    }

    public static function statusChanged(
        Application $application,
        ?string $previousStatus,
        ?string $status,
        ?int $actorId = null,
    ): void {
        self::send(
            application: $application,
            eventLabel: 'Chuyển bước hồ sơ',
            detailLine: 'Trạng thái: '.self::statusLabel($application, $previousStatus)
                .' → '.self::statusLabel($application, $status),
            tone: 'success',
            icon: Heroicon::OutlinedArrowsRightLeft,
            actorId: $actorId,
        );
    }

    public static function integrationFailed(Application $application, string $error): void
    {
        self::send(
            application: $application,
            eventLabel: 'Node-RED đồng bộ thất bại',
            detailLine: 'Lỗi: '.Str::limit($error, 500),
            tone: 'danger',
            icon: Heroicon::OutlinedExclamationTriangle,
            actorId: null,
        );
    }

    public static function feolEligibilityResult(Application $application, bool $eligible): void
    {
        $application->loadMissing('feolIntegration');
        $integration = $application->feolIntegration;
        $customer = $application->applicant_name ?: 'Khách hàng';
        $title = $eligible
            ? 'FEOL - Khách hàng "'.$customer.'" thoả mãn điều kiện đăng ký hồ sơ vay.'
            : 'FEOL - Rất tiếc hồ sơ Khách hàng "'.$customer.'" không thoả mãn điều kiện kiểm tra sơ bộ.';

        self::send(
            application: $application,
            eventLabel: $eligible ? 'Kết quả kiểm tra đủ điều kiện' : 'Kết quả kiểm tra không đủ điều kiện',
            detailLine: implode(' · ', array_filter([
                'Mã hồ sơ: '.$application->application_code,
                'ID đối tác: '.($integration?->partner_lead_id ?: '-'),
                'Trạng thái: '.self::statusLabel($application, $application->status),
                'Khách hàng: '.$customer,
                'SĐT: '.($application->phone ?: '-'),
            ])),
            tone: $eligible ? 'success' : 'danger',
            icon: $eligible ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle,
            actorId: null,
            notificationTitle: $title,
        );
    }

    private static function send(
        Application $application,
        string $eventLabel,
        string $detailLine,
        string $tone,
        Heroicon $icon,
        ?int $actorId,
        array $extraRecipientIds = [],
        ?string $notificationTitle = null,
    ): void {
        try {
            $application->loadMissing([
                'salesProject',
                'createdBy',
                'assignedSale',
                'team',
                'teamLeader',
                'am',
                'zd',
            ]);

            $recipients = self::recipients($application, $actorId, $extraRecipientIds);

            if ($recipients->isEmpty()) {
                return;
            }

            $occurredAt = now()->format('H:i d/m/Y');
            $actor = filled($actorId)
                ? User::query()->whereKey($actorId)->value('name')
                : null;
            $body = self::body(
                $application,
                $eventLabel,
                $detailLine,
                $actor ?: 'Hệ thống',
                $occurredAt,
            );
            $title = $notificationTitle ?: self::title($application);
            $url = self::url($application);

            $recipients->each(function (User $recipient) use ($title, $body, $occurredAt, $icon, $tone, $url): void {
                $notification = Notification::make()
                    ->title($title)
                    ->body($body)
                    ->date($occurredAt)
                    ->icon($icon)
                    ->{$tone}()
                    ->actions([
                        Action::make('openApplication')
                            ->label('Mở hồ sơ')
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

    private static function recipients(
        Application $application,
        ?int $actorId,
        array $extraRecipientIds,
    ): Collection {
        $directIds = collect([
            $application->created_by_id,
            $application->assigned_sale_id,
            $application->team?->manager_id,
            $application->team_leader_id,
            $application->am_id,
            $application->zd_id,
            $application->assignedSale?->courier_manager_id,
            $actorId,
            ...$extraRecipientIds,
        ])
            ->filter()
            ->map(fn (mixed $id): int => (int) $id);

        $managementIds = User::role(['Admin', 'Sales Admin'])->pluck('id');

        return User::query()
            ->whereIn('id', $directIds->merge($managementIds)->unique()->values())
            ->get();
    }

    private static function title(Application $application): string
    {
        return implode(' - ', array_filter([
            'Hồ sơ',
            $application->salesProject?->name ?: 'Application',
            $application->application_code,
            $application->applicant_name,
        ], fn (?string $value): bool => filled($value)));
    }

    private static function body(
        Application $application,
        string $eventLabel,
        string $detailLine,
        string $actor,
        string $occurredAt,
    ): HtmlString {
        $applicationLabel = $application->application_code
            ?: $application->applicant_name
            ?: '#'.$application->getKey();

        return new HtmlString(implode('<br>', [
            '<span class="crm-notification-category" data-category="ho-so">Hồ sơ</span>',
            '<strong>'.e($eventLabel).'</strong>',
            'Hồ sơ: '.e($applicationLabel),
            'Dự án: '.e($application->salesProject?->name ?: 'Application'),
            e($detailLine),
            'Người thao tác: '.e($actor),
            'Thời gian: '.e($occurredAt),
        ]));
    }

    private static function statusLabel(Application $application, mixed $status): string
    {
        if (blank($status)) {
            return 'Chưa có';
        }

        $slug = $application->salesProject?->slug;

        return ProjectWorkflowConfiguration::statusOptions($slug)[(string) $status]
            ?? Str::headline((string) $status);
    }

    private static function url(Application $application): string
    {
        $slug = $application->salesProject?->slug ?: 'acl-mix';

        return url('applications/'.rawurlencode($slug).'/'.$application->getRouteKey());
    }
}
