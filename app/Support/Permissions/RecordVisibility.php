<?php

namespace App\Support\Permissions;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RecordVisibility
{
    private static array $columnCache = [];

    public static function applyUserScope(Builder $query, ?User $user, string $ownerKey, ?string $ownerRelation = null): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user, $ownerKey, $ownerRelation): void {
            $scope->where($ownerKey, $user->getKey());

            if ($ownerKey !== 'created_by_id' && self::hasColumn($scope->getModel()->getTable(), 'created_by_id')) {
                $scope->orWhere('created_by_id', $user->getKey());
            }

            if (! $ownerRelation) {
                return;
            }

            $table = $scope->getModel()->getTable();

            if ($user->hasRole('ZD')) {
                if (self::hasColumn($table, 'zd_id')) {
                    $scope->orWhere('zd_id', $user->getKey());
                }
                $scope->orWhereHas($ownerRelation, fn (Builder $owner): Builder => $owner->where('zd_id', $user->getKey()));
            } elseif ($user->hasRole('AM')) {
                if (self::hasColumn($table, 'am_id')) {
                    $scope->orWhere('am_id', $user->getKey());
                }
                $scope->orWhereHas($ownerRelation, fn (Builder $owner): Builder => $owner->where('am_id', $user->getKey()));
            } elseif ($user->hasRole('Team Leader')) {
                if (self::hasColumn($table, 'team_leader_id')) {
                    $scope->orWhere('team_leader_id', $user->getKey());
                }
                $scope->orWhereHas($ownerRelation, fn (Builder $owner): Builder => $owner->where('team_leader_id', $user->getKey()));
            } elseif ($user->hasRole('Courier Manager')) {
                $scope->orWhereHas($ownerRelation, fn (Builder $owner): Builder => $owner->where('courier_manager_id', $user->getKey()));
            }
        });
    }

    public static function canAccessUserOwnedRecord(?User $user, Model $record, string $ownerKey, ?string $ownerRelation = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        if (isset($record->created_by_id) && (int) $record->created_by_id === (int) $user->getKey()) {
            return true;
        }

        if ((int) $record->{$ownerKey} === (int) $user->getKey()) {
            return true;
        }

        if (! $ownerRelation || ! method_exists($record, $ownerRelation)) {
            return false;
        }

        $owner = $record->{$ownerRelation};

        if (! $owner instanceof User) {
            return false;
        }

        if ($user->hasRole('ZD')) {
            return (int) ($record->zd_id ?? 0) === (int) $user->getKey()
                || (int) $owner->zd_id === (int) $user->getKey();
        }

        if ($user->hasRole('AM')) {
            return (int) ($record->am_id ?? 0) === (int) $user->getKey()
                || (int) $owner->am_id === (int) $user->getKey();
        }

        if ($user->hasRole('Team Leader')) {
            return (int) ($record->team_leader_id ?? 0) === (int) $user->getKey()
                || (int) $owner->team_leader_id === (int) $user->getKey();
        }

        if ($user->hasRole('Courier Manager')) {
            return (int) $owner->courier_manager_id === (int) $user->getKey();
        }

        return false;
    }

    private static function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        return self::$columnCache[$key] ??= Schema::hasColumn($table, $column);
    }
}
