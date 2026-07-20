<?php

namespace App\Support\Permissions;

use App\Models\DataCenterLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DataCenterAccess
{
    private const MANAGER_ROLES = ['Admin', 'ZD', 'AM', 'Team Leader'];

    public static function canAccessModule(?User $user): bool
    {
        return $user instanceof User && ! in_array($user->employment_status, [
            'inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED,
        ], true);
    }

    public static function canDistribute(?User $user): bool
    {
        return self::canAccessModule($user) && $user->hasAnyRole(self::MANAGER_ROLES);
    }

    public static function canView(?User $user, DataCenterLead $record): bool
    {
        if (! self::canAccessModule($user)) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        $id = (int) $user->getKey();

        return (int) $record->assigned_user_id === $id
            || ($user->hasAnyRole(['ZD', 'AM', 'Team Leader']) && (int) $record->created_by_id === $id)
            || ($user->hasRole('ZD') && (int) $record->zd_id === $id)
            || ($user->hasRole('AM') && (int) $record->am_id === $id)
            || ($user->hasRole('Team Leader') && (int) $record->team_leader_id === $id);
    }

    public static function canUpdateResult(?User $user, DataCenterLead $record): bool
    {
        return self::canView($user, $record)
            && ($user?->hasRole('Admin') || (int) $record->assigned_user_id === (int) $user?->getKey());
    }

    public static function canConvert(?User $user, DataCenterLead $record): bool
    {
        $conversionCount = $record->relationLoaded('conversions')
            ? $record->conversions->count()
            : $record->conversions()->count();

        return self::canUpdateResult($user, $record)
            && in_array($record->status, ['qualified', 'converted_once'], true)
            && $conversionCount < 2;
    }

    public static function applyVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! self::canAccessModule($user)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Admin')) {
            return $query;
        }

        $id = $user->getKey();

        return $query->where(function (Builder $query) use ($user, $id): void {
            $query->where('assigned_user_id', $id);

            if ($user->hasAnyRole(['ZD', 'AM', 'Team Leader'])) {
                $query->orWhere('created_by_id', $id);
            }

            if ($user->hasRole('ZD')) {
                $query->orWhere('zd_id', $id);
            }

            if ($user->hasRole('AM')) {
                $query->orWhere('am_id', $id);
            }

            if ($user->hasRole('Team Leader')) {
                $query->orWhere('team_leader_id', $id);
            }
        });
    }

    public static function assignableUsers(?User $actor, ?string $search = null): Builder
    {
        $query = User::query()
            ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED]);

        if (! $actor?->hasRole('Admin')) {
            $query->where(function (Builder $query) use ($actor): void {
                if ($actor?->hasRole('ZD')) {
                    $query->where('zd_id', $actor->getKey());
                } elseif ($actor?->hasRole('AM')) {
                    $query->where('am_id', $actor->getKey());
                } elseif ($actor?->hasRole('Team Leader')) {
                    $query->where('team_leader_id', $actor->getKey());
                } else {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        return $query
            ->when(filled($search), fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('uid', 'ilike', "%{$search}%")
                    ->orWhere('employee_code', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->orderBy('name');
    }

    public static function canAssignUser(?User $actor, User $assignee): bool
    {
        return self::canDistribute($actor)
            && self::assignableUsers($actor)->whereKey($assignee->getKey())->exists();
    }

    public static function userLabel(User $user): string
    {
        return implode(' · ', array_filter([$user->name, $user->uid, $user->employee_code]));
    }
}
