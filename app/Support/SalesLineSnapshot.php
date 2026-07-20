<?php

namespace App\Support;

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
            'team_id' => $user->team_id,
            'team_leader_id' => self::teamLeaderId($user),
            'am_id' => self::amId($user),
            'zd_id' => self::zdId($user),
        ];
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
