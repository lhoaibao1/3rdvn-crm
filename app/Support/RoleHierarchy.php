<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class RoleHierarchy
{
    public const ORDER = ['Admin', 'ZD', 'AM', 'Team Leader', 'Direct Sale', 'Telesale', 'CTV'];

    public const SALES_ROLES = ['Direct Sale', 'Telesale', 'CTV'];

    public const ASSIGNABLE = [
        'Admin' => ['Admin', 'ZD', 'AM', 'Team Leader', 'Direct Sale', 'Telesale', 'CTV'],
        'ZD' => ['AM', 'Team Leader', 'Direct Sale', 'Telesale', 'CTV'],
        'AM' => ['Team Leader', 'Direct Sale', 'Telesale', 'CTV'],
        'Team Leader' => ['Direct Sale', 'Telesale', 'CTV'],
        'Direct Sale' => [],
        'Telesale' => [],
        'CTV' => [],
    ];

    public static function primaryRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $roles = $user->relationLoaded('roles')
            ? $user->roles->pluck('name')->all()
            : $user->roles()->pluck('name')->all();

        foreach (self::ORDER as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return null;
    }

    public static function assignableRoles(?User $actor = null): array
    {
        $actor ??= auth()->user();
        $role = self::primaryRole($actor);

        return $role ? (self::ASSIGNABLE[$role] ?? []) : [];
    }

    public static function assignableRoleOptions(?User $actor = null): array
    {
        return collect(self::assignableRoles($actor))
            ->mapWithKeys(fn (string $role): array => [$role => $role])
            ->all();
    }

    public static function canCreateUsers(?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return $actor instanceof User
            && $actor->can('user.create')
            && self::assignableRoles($actor) !== [];
    }

    public static function canAssignRole(?User $actor, ?string $role): bool
    {
        if (! $actor || blank($role)) {
            return false;
        }

        return in_array($role, self::assignableRoles($actor), true);
    }

    public static function canManageUser(?User $actor, User $target): bool
    {
        if (! $actor) {
            return false;
        }

        if ($actor->getKey() === $target->getKey()) {
            return $actor->hasRole('Admin');
        }

        $targetRole = self::primaryRole($target);

        if (! self::canAssignRole($actor, $targetRole)) {
            return false;
        }

        if ($actor->hasRole('Admin')) {
            return true;
        }

        if ($actor->hasRole('ZD')) {
            return self::targetBelongsToManager($target, 'zd_id', $actor->getKey());
        }

        if ($actor->hasRole('AM')) {
            return self::targetBelongsToManager($target, 'am_id', $actor->getKey());
        }

        if ($actor->hasRole('Team Leader')) {
            return self::targetBelongsToManager($target, 'team_leader_id', $actor->getKey());
        }

        return false;
    }

    public static function canUpdateUser(?User $actor, User $target): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        if (in_array($target->employment_status, [User::STATUS_DEACTIVE, User::STATUS_DELETED, 'inactive', 'resigned'], true)) {
            return $actor->hasRole('Admin');
        }

        if ($actor->getKey() === $target->getKey()) {
            return $actor->hasRole('Admin');
        }

        return self::canManageUser($actor, $target);
    }

    public static function canUseRoleOnEdit(?User $actor, User $target, ?string $role): bool
    {
        if (! $actor instanceof User || blank($role)) {
            return false;
        }

        if ($actor->hasRole('Admin')) {
            return self::canAssignRole($actor, $role);
        }

        return $role === self::primaryRole($target)
            && self::canUpdateUser($actor, $target);
    }

    public static function sanitizeProtectedUpdateData(array $data, User $target, ?User $actor): array
    {
        if ($actor?->hasRole('Admin')) {
            return $data;
        }

        unset(
            $data['uid'],
            $data['employee_code'],
            $data['password'],
            $data['email_verified_at'],
            $data['employment_status'],
            $data['hire_date'],
            $data['office'],
            $data['contract_type'],
            $data['sales_projects'],
            $data['sales_codes'],
            $data['company_name'],
            $data['branch_name'],
            $data['branch_code'],
            $data['sales_channel']
        );

        return $data;
    }

    public static function applyVisibilityScope(Builder $query, ?User $actor = null): Builder
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($actor->hasRole('Admin')) {
            return $query;
        }

        $assignable = self::assignableRoles($actor);

        if ($assignable === []) {
            return $query->whereKey($actor->getKey());
        }

        if ($actor->hasRole('ZD')) {
            return $query->where(function (Builder $query) use ($actor, $assignable): void {
                $query->whereKey($actor->getKey())
                    ->orWhere(function (Builder $query) use ($actor, $assignable): void {
                        $query
                            ->whereHas('roles', fn (Builder $roles): Builder => $roles->whereIn('name', $assignable))
                            ->where('zd_id', $actor->getKey());
                    });
            });
        }

        if ($actor->hasRole('AM')) {
            return $query->where(function (Builder $query) use ($actor, $assignable): void {
                $query->whereKey($actor->getKey())
                    ->orWhere(function (Builder $query) use ($actor, $assignable): void {
                        $query
                            ->whereHas('roles', fn (Builder $roles): Builder => $roles->whereIn('name', $assignable))
                            ->where('am_id', $actor->getKey());
                    });
            });
        }

        if ($actor->hasRole('Team Leader')) {
            return $query->where(function (Builder $query) use ($actor, $assignable): void {
                $query->whereKey($actor->getKey())
                    ->orWhere(function (Builder $query) use ($actor, $assignable): void {
                        $query
                            ->whereHas('roles', fn (Builder $roles): Builder => $roles->whereIn('name', $assignable))
                            ->where('team_leader_id', $actor->getKey());
                    });
            });
        }

        return $query->whereKey($actor->getKey());
    }

    public static function managerFieldDefaults(?User $actor, ?string $role): array
    {
        $data = [
            'zd_id' => null,
            'am_id' => null,
            'team_leader_id' => null,
        ];

        if ($actor instanceof User) {
            if ($actor->hasRole('ZD')) {
                $data['zd_id'] = $actor->getKey();
            }

            if ($actor->hasRole('AM')) {
                $data['zd_id'] = $actor->zd_id;
                $data['am_id'] = $actor->getKey();
            }

            if ($actor->hasRole('Team Leader')) {
                $data['zd_id'] = $actor->zd_id;
                $data['am_id'] = $actor->am_id;
                $data['team_leader_id'] = $actor->getKey();
            }
        }

        if (in_array($role, ['Admin', 'ZD'], true)) {
            return [
                'zd_id' => null,
                'am_id' => null,
                'team_leader_id' => null,
            ];
        }

        if ($role === 'AM') {
            $data['am_id'] = null;
            $data['team_leader_id'] = null;
        }

        if ($role === 'Team Leader') {
            $data['team_leader_id'] = null;
        }

        return $data;
    }

    public static function normalizeManagerFields(array $data, ?User $actor, string $role): array
    {
        $data = self::applySelectedManagerChain($data);

        if ($actor instanceof User) {
            if ($actor->hasRole('ZD')) {
                $data['zd_id'] = $actor->getKey();
            }

            if ($actor->hasRole('AM')) {
                $data['am_id'] = $actor->getKey();
                $data['zd_id'] = $actor->zd_id;
            }

            if ($actor->hasRole('Team Leader')) {
                $data['team_leader_id'] = $actor->getKey();
                $data['am_id'] = $actor->am_id;
                $data['zd_id'] = $actor->zd_id;
            }
        }

        if (in_array($role, ['Admin', 'ZD'], true)) {
            $data['zd_id'] = null;
            $data['am_id'] = null;
            $data['team_leader_id'] = null;
        }

        if ($role === 'AM') {
            $data['am_id'] = null;
            $data['team_leader_id'] = null;
        }

        if ($role === 'Team Leader') {
            $data['team_leader_id'] = null;
        }

        return $data;
    }

    public static function validateManagerFields(array $data, ?User $actor, string $role): void
    {
        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'roles' => 'Phiên đăng nhập không hợp lệ.',
            ]);
        }

        if (in_array($role, ['Admin', 'ZD'], true)) {
            return;
        }

        if ($role === 'AM') {
            $zd = self::requiredManager($data['zd_id'] ?? null, 'ZD', 'zd_id', 'Vui lòng chọn ZD cho AM.');

            if ($actor->hasRole('ZD') && (int) $zd->getKey() !== (int) $actor->getKey()) {
                self::deny('zd_id', 'ZD chỉ được tạo AM thuộc chính mình.');
            }

            return;
        }

        if ($role === 'Team Leader') {
            $am = self::requiredManager($data['am_id'] ?? null, 'AM', 'am_id', 'Vui lòng chọn AM cho Team Leader.');

            if (blank($am->zd_id)) {
                self::deny('am_id', 'AM này chưa được gắn ZD.');
            }

            if ((int) ($data['zd_id'] ?? 0) !== (int) $am->zd_id) {
                self::deny('am_id', 'AM không thuộc ZD đã chọn.');
            }

            if ($actor->hasRole('ZD') && (int) $am->zd_id !== (int) $actor->getKey()) {
                self::deny('am_id', 'ZD chỉ được tạo Team Leader thuộc tuyến của mình.');
            }

            if ($actor->hasRole('AM') && (int) $am->getKey() !== (int) $actor->getKey()) {
                self::deny('am_id', 'AM chỉ được tạo Team Leader thuộc chính mình.');
            }

            return;
        }

        if (in_array($role, self::SALES_ROLES, true)) {
            $teamLeader = self::requiredManager($data['team_leader_id'] ?? null, 'Team Leader', 'team_leader_id', 'Vui lòng chọn Team Leader cho sale.');

            if (blank($teamLeader->am_id)) {
                self::deny('team_leader_id', 'Team Leader này chưa được gắn AM.');
            }

            if (blank($teamLeader->zd_id)) {
                self::deny('team_leader_id', 'Team Leader này chưa được gắn ZD.');
            }

            if ((int) ($data['am_id'] ?? 0) !== (int) $teamLeader->am_id) {
                self::deny('team_leader_id', 'Team Leader không thuộc AM đã chọn.');
            }

            if ((int) ($data['zd_id'] ?? 0) !== (int) $teamLeader->zd_id) {
                self::deny('team_leader_id', 'Team Leader không thuộc ZD đã chọn.');
            }

            if ($actor->hasRole('ZD') && (int) $teamLeader->zd_id !== (int) $actor->getKey()) {
                self::deny('team_leader_id', 'ZD chỉ được tạo sale thuộc tuyến của mình.');
            }

            if ($actor->hasRole('AM') && (int) $teamLeader->am_id !== (int) $actor->getKey()) {
                self::deny('team_leader_id', 'AM chỉ được tạo sale dưới Team Leader của mình.');
            }

            if ($actor->hasRole('Team Leader') && (int) $teamLeader->getKey() !== (int) $actor->getKey()) {
                self::deny('team_leader_id', 'Team Leader chỉ được tạo sale thuộc chính mình.');
            }
        }
    }

    private static function applySelectedManagerChain(array $data): array
    {
        if (filled($data['team_leader_id'] ?? null)) {
            $teamLeader = User::query()->find($data['team_leader_id']);

            if ($teamLeader instanceof User) {
                $data['am_id'] = $teamLeader->am_id;
                $data['zd_id'] = $teamLeader->zd_id;
            }

            return $data;
        }

        if (filled($data['am_id'] ?? null)) {
            $am = User::query()->find($data['am_id']);

            if ($am instanceof User) {
                $data['zd_id'] = $am->zd_id;
            }
        }

        return $data;
    }

    private static function requiredManager(mixed $id, string $role, string $field, string $message): User
    {
        if (blank($id)) {
            self::deny($field, $message);
        }

        $user = User::query()->find($id);

        if (! $user instanceof User || ! $user->hasRole($role)) {
            self::deny($field, $message);
        }

        return $user;
    }

    private static function deny(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }

    private static function targetBelongsToManager(User $target, string $field, int $actorId): bool
    {
        return (int) $target->{$field} === $actorId;
    }
}
