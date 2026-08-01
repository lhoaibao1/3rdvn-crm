<?php

namespace App\Support;

use App\Models\CrmTeam;
use App\Models\User;

class SalesLineSnapshot
{
    public static function fromUser(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                'assigned_sale_id' => null,
                'created_by_id' => null,
                'team_id' => null,
                'team_leader_id' => null,
                'am_id' => null,
                'zd_id' => null,
            ];
        }

        return [
            'assigned_sale_id' => $user->getKey(),
            'created_by_id' => $user->getKey(),
            'team_id' => self::teamId($user),
            'team_leader_id' => self::teamLeaderId($user),
            'am_id' => self::amId($user),
            'zd_id' => self::zdId($user),
        ];
    }

    public static function hierarchyFromUser(?User $user): array
    {
        $snapshot = self::fromUser($user);

        return [
            'team_id' => $snapshot['team_id'],
            'team_leader_id' => $snapshot['team_leader_id'],
            'am_id' => $snapshot['am_id'],
            'zd_id' => $snapshot['zd_id'],
        ];
    }

    public static function hierarchyForUserId(mixed $userId): array
    {
        return self::hierarchyFromUser(filled($userId) ? User::query()->find($userId) : null);
    }

    public static function fromLeadLike(object $record): array
    {
        return [
            'assigned_sale_id' => $record->assigned_sale_id ?? null,
            'created_by_id' => $record->created_by_id ?? ($record->assigned_sale_id ?? null),
            'team_id' => $record->team_id ?? null,
            'team_leader_id' => $record->team_leader_id ?? null,
            'am_id' => $record->am_id ?? null,
            'zd_id' => $record->zd_id ?? null,
        ];
    }

    private static function teamId(User $user): ?int
    {
        if (filled($user->team_id)) {
            return (int) $user->team_id;
        }

        return CrmTeam::query()->where("manager_id", $user->getKey())->value("id");
    }

    private static function teamLeaderId(User $user): ?int
    {
        if ($user->hasRole('Team Leader')) {
            return $user->getKey();
        }

        return $user->team_leader_id;
    }

    private static function amId(User $user): ?int
    {
        if ($user->hasRole('AM')) {
            return $user->getKey();
        }

        return $user->am_id;
    }

    private static function zdId(User $user): ?int
    {
        if ($user->hasRole('ZD')) {
            return $user->getKey();
        }

        return $user->zd_id;
    }
}
