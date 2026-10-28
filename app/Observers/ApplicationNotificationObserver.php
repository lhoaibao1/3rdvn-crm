<?php

namespace App\Observers;

use App\Models\Application;
use App\Support\Notifications\ApplicationNotificationSender;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ApplicationNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Application $application): void
    {
        ApplicationNotificationSender::created($application, auth()->id());
    }

    public function updated(Application $application): void
    {
        $actorId = auth()->id();

        $application->loadMissing('salesProject');
        if (($application->salesProject?->slug ?: null) === 'fe-deeplink' && blank($actorId)) {
            return;
        }

        if ($application->wasChanged('assigned_sale_id')) {
            ApplicationNotificationSender::assigned(
                $application,
                self::nullableId($application->getOriginal('assigned_sale_id')),
                self::nullableId($application->assigned_sale_id),
                $actorId,
            );
        }

        if ($application->wasChanged('status')) {
            ApplicationNotificationSender::statusChanged(
                $application,
                self::nullableString($application->getOriginal('status')),
                self::nullableString($application->status),
                $actorId,
            );
        }
    }

    private static function nullableId(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }
}
