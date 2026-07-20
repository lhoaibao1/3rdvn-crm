<?php

namespace App\Support\Candidates;

use App\Filament\Resources\CandidateApplications\CandidateApplicationResource;
use App\Models\CandidateApplication;
use App\Models\User;
use App\Support\RoleHierarchy;
use Filament\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class CandidateWorkflow
{
    public const ACCESS_ROLES = ['Admin', 'ZD', 'AM', 'Team Leader'];
    public const INTERVIEWER_ROLES = ['ZD', 'AM', 'Team Leader'];

    public static function canAccess(?User $user): bool
    {
        return $user instanceof User && $user->hasAnyRole(self::ACCESS_ROLES);
    }

    public static function scopeVisible(Builder $query, ?User $actor = null): Builder
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($actor->hasRole('Admin')) {
            return $query;
        }

        if ($actor->hasRole('ZD')) {
            return $query->where(function (Builder $candidateQuery) use ($actor): void {
                $candidateQuery
                    ->whereNull('assigned_to_id')
                    ->orWhere('assigned_to_id', $actor->getKey())
                    ->orWhereHas('assignedTo', fn (Builder $userQuery): Builder => $userQuery->where('zd_id', $actor->getKey()));
            });
        }

        return $query->where('assigned_to_id', $actor->getKey());
    }

    public static function canView(CandidateApplication $candidate, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return self::canAccess($actor)
            && self::scopeVisible(CandidateApplication::query()->whereKey($candidate->getKey()), $actor)->exists();
    }

    public static function canEdit(CandidateApplication $candidate, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return self::canView($candidate, $actor)
            && $actor->hasAnyRole(['Admin', 'ZD'])
            && ! $candidate->converted_user_id;
    }

    public static function canAssign(CandidateApplication $candidate, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return self::canView($candidate, $actor)
            && $actor->hasAnyRole(['Admin', 'ZD'])
            && in_array($candidate->status, [
                CandidateApplication::STATUS_NEW,
                CandidateApplication::STATUS_REVIEWING,
                CandidateApplication::STATUS_ASSIGNED,
                CandidateApplication::STATUS_INTERVIEWING,
            ], true)
            && ! $candidate->converted_user_id;
    }

    public static function canInterview(CandidateApplication $candidate, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return $actor instanceof User
            && $candidate->assigned_to_id === $actor->getKey()
            && in_array($candidate->status, [
                CandidateApplication::STATUS_ASSIGNED,
                CandidateApplication::STATUS_INTERVIEWING,
            ], true)
            && ! $candidate->converted_user_id;
    }

    public static function canApprove(CandidateApplication $candidate, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return self::canView($candidate, $actor)
            && $actor->hasAnyRole(['Admin', 'ZD'])
            && $candidate->status === CandidateApplication::STATUS_PENDING_APPROVAL
            && ! $candidate->converted_user_id;
    }

    public static function canIssueCode(CandidateApplication $candidate, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return self::canView($candidate, $actor)
            && $actor->hasAnyRole(['Admin', 'ZD'])
            && $candidate->status === CandidateApplication::STATUS_APPROVED
            && ! $candidate->converted_user_id;
    }

    public static function assigneeOptions(?User $actor = null): array
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User || ! $actor->hasAnyRole(['Admin', 'ZD'])) {
            return [];
        }

        $query = User::query()
            ->role(self::INTERVIEWER_ROLES)
            ->where('employment_status', User::STATUS_ACTIVE);

        if ($actor->hasRole('ZD') && ! $actor->hasRole('Admin')) {
            $query->where(function (Builder $userQuery) use ($actor): void {
                $userQuery
                    ->whereKey($actor->getKey())
                    ->orWhere('zd_id', $actor->getKey());
            });
        }

        return $query
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (User $user): array {
                $role = RoleHierarchy::primaryRole($user) ?: 'Quản lý';
                $unit = $user->branch_name ?: $user->company_name ?: $user->uid;

                return [$user->getKey() => implode(' - ', array_filter([$user->name, $role, $unit]))];
            })
            ->all();
    }

    public static function assign(CandidateApplication $candidate, int $assigneeId, User $actor): CandidateApplication
    {
        if (! self::canAssign($candidate, $actor)) {
            abort(403);
        }

        $assignee = User::query()
            ->whereKey($assigneeId)
            ->whereIn('id', array_keys(self::assigneeOptions($actor)))
            ->first();

        if (! $assignee) {
            throw ValidationException::withMessages([
                'assigned_to_id' => 'Người phỏng vấn không thuộc phạm vi được phép phân công.',
            ]);
        }

        DB::transaction(function () use ($candidate, $assignee, $actor): void {
            $candidate->forceFill([
                'status' => CandidateApplication::STATUS_ASSIGNED,
                'assigned_to_id' => $assignee->getKey(),
                'assigned_by_id' => $actor->getKey(),
                'assigned_at' => now(),
                'interview_at' => null,
                'interview_note' => null,
                'interview_recommendation' => null,
                'submitted_at' => null,
                'approved_by_id' => null,
                'approved_at' => null,
                'approval_note' => null,
            ])->save();
        });

        $candidate->refresh()->loadMissing('assignedTo');
        self::deliver(
            self::assigneeAndAdmins($assignee),
            $candidate,
            'Bạn được giao ứng viên để phỏng vấn',
            $actor,
            'info',
            Heroicon::OutlinedUserPlus,
        );

        return $candidate;
    }

    public static function startInterview(CandidateApplication $candidate, User $actor): CandidateApplication
    {
        if (! self::canInterview($candidate, $actor)) {
            abort(403);
        }

        $candidate->forceFill([
            'status' => CandidateApplication::STATUS_INTERVIEWING,
            'interview_at' => $candidate->interview_at ?: now(),
        ])->save();

        return $candidate->refresh();
    }

    public static function submitInterview(CandidateApplication $candidate, array $data, User $actor): CandidateApplication
    {
        if (! self::canInterview($candidate, $actor)) {
            abort(403);
        }

        if (! in_array($data['interview_recommendation'] ?? null, ['hire', 'reject'], true)) {
            throw ValidationException::withMessages([
                'interview_recommendation' => 'Vui lòng chọn đề xuất sau phỏng vấn.',
            ]);
        }

        $candidate->forceFill([
            'status' => CandidateApplication::STATUS_PENDING_APPROVAL,
            'interview_at' => $data['interview_at'] ?? now(),
            'interview_note' => $data['interview_note'] ?? null,
            'interview_recommendation' => $data['interview_recommendation'],
            'submitted_at' => now(),
        ])->save();

        $candidate->refresh()->loadMissing('assignedTo');
        self::deliver(
            self::approvalRecipients($candidate),
            $candidate,
            'Hồ sơ phỏng vấn đang chờ phê duyệt tuyển dụng',
            $actor,
            'warning',
            Heroicon::OutlinedClipboardDocumentCheck,
        );

        return $candidate;
    }

    public static function decide(CandidateApplication $candidate, bool $approved, ?string $note, User $actor): CandidateApplication
    {
        if (! self::canApprove($candidate, $actor)) {
            abort(403);
        }

        $candidate->forceFill([
            'status' => $approved
                ? CandidateApplication::STATUS_APPROVED
                : CandidateApplication::STATUS_REJECTED,
            'approved_by_id' => $actor->getKey(),
            'approved_at' => now(),
            'approval_note' => $note,
            'reviewed_by_id' => $actor->getKey(),
            'reviewed_at' => now(),
        ])->save();

        $candidate->refresh()->loadMissing('assignedTo');
        self::deliver(
            self::assigneeAndAdmins($candidate->assignedTo),
            $candidate,
            $approved ? 'Đã phê duyệt tuyển dụng' : 'Không phê duyệt tuyển dụng',
            $actor,
            $approved ? 'success' : 'danger',
            $approved ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle,
        );

        return $candidate;
    }

    private static function assigneeAndAdmins(?User $assignee): Collection
    {
        $ids = User::role('Admin')->pluck('id');

        if ($assignee) {
            $ids->push($assignee->getKey());
        }

        return User::query()->whereIn('id', $ids->unique())->get();
    }

    private static function approvalRecipients(CandidateApplication $candidate): Collection
    {
        $ids = User::role('Admin')->pluck('id');
        $assignee = $candidate->assignedTo;

        if ($assignee?->hasRole('ZD')) {
            $ids->push($assignee->getKey());
        }

        if ($assignee?->zd_id) {
            $ids->push($assignee->zd_id);
        }

        return User::query()->whereIn('id', $ids->unique())->get();
    }

    private static function deliver(
        Collection $recipients,
        CandidateApplication $candidate,
        string $event,
        User $actor,
        string $tone,
        Heroicon $icon,
    ): void {
        try {
            $url = CandidateApplicationResource::getUrl('view', ['record' => $candidate]);
            $title = implode(' - ', array_filter(['CV', $candidate->application_code, $candidate->full_name]));
            $body = new HtmlString(implode('<br>', [
                '<span class="crm-notification-category" data-category="he-thong">Hệ thống</span>',
                '<strong>'.e($event).'</strong>',
                'Người thao tác: '.e($actor->name ?: $actor->uid ?: 'Hệ thống'),
                'Thời gian: '.e(now()->format('H:i d/m/Y')),
            ]));

            $recipients->each(function (User $recipient) use ($title, $body, $icon, $tone, $url): void {
                $notification = Notification::make()
                    ->title($title)
                    ->body($body)
                    ->icon($icon)
                    ->{$tone}()
                    ->actions([
                        Action::make('openCandidate')
                            ->label('Mở hồ sơ ứng viên')
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
}
