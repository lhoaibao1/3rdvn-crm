<?php

namespace App\Support\Notifications;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Jobs\SendWebPushNotification;
use App\Models\Application;
use App\Models\User;
use App\Support\Applications\ProjectWorkflowConfiguration;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Throwable;

class ApplicationNotificationSender
{
    public static function created(Application $application): void
    {
        self::sendToRecipients(
            application: $application,
            eventLabel: 'Hồ sơ mới tạo',
            actorLabel: 'Người tạo',
            status: 'warning',
            icon: Heroicon::OutlinedBellAlert,
            detailLine: self::createdDetailLine($application),
            occurredAt: $application->created_at?->format('H:i d/m/Y') ?: now()->format('H:i d/m/Y'),
        );
    }

    public static function updated(Application $application, array $changes = []): void
    {
        $statusNew = data_get($changes, 'status.new');
        $statusOld = data_get($changes, 'status.old');

        if (filled($statusNew) && $statusNew !== $statusOld) {
            $newLabel = self::statusLabel($application, $statusNew);
            $oldLabel = self::statusLabel($application, $statusOld);

            self::sendToRecipients(
                application: $application,
                eventLabel: 'Cập nhật trạng thái',
                actorLabel: 'Người cập nhật',
                status: 'info',
                icon: Heroicon::OutlinedPencilSquare,
                detailLine: "Trạng thái: {$oldLabel} → {$newLabel}",
                occurredAt: now()->format('H:i d/m/Y'),
            );
        }
    }

    public static function disbursed(Application $application): void
    {
        $amountFormatted = $application->approved_amount && $application->approved_amount > 0
            ? (number_format($application->approved_amount, 0, ',', '.') . ' VNĐ')
            : ($application->loan_amount ? (number_format($application->loan_amount, 0, ',', '.') . ' VNĐ') : 'Thành công');

        self::sendToRecipients(
            application: $application,
            eventLabel: 'Giải ngân thành công',
            actorLabel: 'Người cập nhật',
            status: 'success',
            icon: Heroicon::OutlinedCheckCircle,
            detailLine: "Số tiền giải ngân: {$amountFormatted}",
            occurredAt: now()->format('H:i d/m/Y'),
        );
    }

    public static function rejected(Application $application, ?string $reason = null): void
    {
        self::sendToRecipients(
            application: $application,
            eventLabel: 'Hồ sơ bị từ chối',
            actorLabel: 'Người cập nhật',
            status: 'danger',
            icon: Heroicon::OutlinedXCircle,
            detailLine: $reason ? "Lý do: {$reason}" : 'Không đủ điều kiện',
            occurredAt: now()->format('H:i d/m/Y'),
        );
    }

    private static function sendToRecipients(
        Application $application,
        string $eventLabel,
        string $actorLabel,
        string $status,
        Heroicon $icon,
        string $detailLine,
        string $occurredAt,
    ): void {
        try {
            $application->loadMissing(['salesProject', 'createdBy', 'assignedSale.courierManager', 'teamLeader', 'am', 'zd']);
            $recipients = self::recipients($application);

            if ($recipients->isEmpty()) {
                return;
            }

            $actor = auth()->user()?->name ?: $application->createdBy?->name ?: 'Hệ thống';
            $caseId = $application->application_code ?: ('#' . $application->getKey());
            $customerName = $application->applicant_name ?: 'Khách hàng';
            $projectName = $application->salesProject?->name ?: 'Hồ sơ tài chính';

            $amount = (float) ($application->approved_amount ?: $application->loan_amount ?: 0);
            $amountFormatted = $amount > 0 ? (number_format($amount, 0, ',', '.') . ' VNĐ') : null;

            if ($status === 'success' && $amountFormatted) {
                $title = "🎉 [CaseID: {$caseId}] Giải ngân {$amountFormatted} - {$customerName} ({$projectName})";
            } elseif ($status === 'danger') {
                $title = "❌ [CaseID: {$caseId}] Từ chối hồ sơ - {$customerName} ({$projectName})";
            } else {
                $title = "🔔 [CaseID: {$caseId}] {$customerName} - {$projectName}";
            }

            $bodyLines = [
                "👤 Khách hàng: {$customerName}",
                "🏢 Dự án: {$projectName}",
                "🔢 Mã GD: {$caseId}",
                "📋 {$eventLabel}",
                "📌 {$detailLine}",
            ];

            if ($amountFormatted && ($status === 'success' || !str_contains($detailLine, 'giải ngân'))) {
                $bodyLines[] = "💰 Số tiền: {$amountFormatted}";
            }

            $bodyLines[] = "👨‍💼 {$actorLabel}: {$actor}";
            $bodyLines[] = "⏰ Thời gian: {$occurredAt}";

            $bodyText = implode("\n", array_filter($bodyLines));
            $url = ApplicationResource::getUrl('view', ['record' => $application]);

            $recipients->each(function (User $recipient) use ($title, $bodyText, $occurredAt, $icon, $status, $url): void {
                $notification = Notification::make()
                    ->title($title)
                    ->body($bodyText)
                    ->date($occurredAt)
                    ->icon($icon)
                    ->{$status}()
                    ->actions([
                        Action::make('openApp')
                            ->label('Xem chi tiết')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->button()
                            ->markAsRead()
                            ->url($url),
                    ]);

                $recipient->notifyNow($notification->toDatabase());
                DatabaseNotificationsSent::dispatch($recipient);
            });

            try {
                SendWebPushNotification::dispatch($recipients->modelKeys(), [
                    'title' => $title,
                    'body' => $bodyText,
                    'url' => $url,
                    'tag' => 'app-'.$application->id.'-'.$status,
                ]);
            } catch (Throwable $e) {}

        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function createdDetailLine(Application $application): string
    {
        if ($application->loan_amount && $application->loan_amount > 0) {
            return 'Nhu cầu vay: ' . number_format($application->loan_amount, 0, ',', '.') . ' VNĐ';
        }
        return 'Tạo mới trên hệ thống';
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

    private static function recipients(Application $application): Collection
    {
        $directIds = collect([
            $application->created_by_id,
            $application->assigned_sale_id,
            $application->team_leader_id,
            $application->am_id,
            $application->zd_id,
            $application->assignedSale?->courier_manager_id,
        ])->filter()->map(fn (mixed $id): int => (int) $id);

        $adminIds = User::role('Admin')->pluck('id');

        return User::query()
            ->whereIn('id', $directIds->merge($adminIds)->unique()->values())
            ->get();
    }
}
