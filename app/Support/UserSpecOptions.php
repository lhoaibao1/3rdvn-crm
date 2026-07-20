<?php

namespace App\Support;

use App\Models\CrmLookup;
use App\Models\SalesProject;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserSpecOptions
{
    public static function primaryRoleNames(): array
    {
        return ['Admin', 'ZD', 'AM', 'Team Leader', 'Courier Manager', 'Courier', 'Direct Sale', 'Telesale', 'CTV'];
    }

    public static function documentTypes(): array
    {
        return self::lookupOptions('document_type', [
            'citizen_id' => 'Căn cước',
            'cccd' => 'Căn cước công dân',
            'passport' => 'Hộ chiếu',
        ]);
    }

    public static function issuedPlaces(): array
    {
        return self::lookupOptions('issued_place', [
            'ccs' => 'CCS',
            'bo_cong_an' => 'Bộ Công An',
        ]);
    }

    public static function departments(): array
    {
        return self::lookupOptions('department', [
            'CVTVTD' => 'CVTVTD',
            'CVTV' => 'CVTV',
            'TTLK' => 'TTLK',
        ]);
    }

    public static function employmentStatuses(): array
    {
        return self::lookupOptions('employment_status', [
            'active' => 'Hoạt động',
            'deactive' => 'Không hoạt động',
            'deleted' => 'Đã xoá',
        ]);
    }

    public static function offices(): array
    {
        return self::lookupOptions('office', [
            '3RDVN - HCMC' => '3RDVN - HCMC',
            '3RDVN - Online' => '3RDVN - Online',
        ]);
    }

    public static function contractTypes(): array
    {
        return self::lookupOptions('contract_type', [
            'collaborator' => 'Cộng tác viên',
            'probation' => 'Nhân viên thử việc',
            'official' => 'Nhân viên chính thức',
            'partner' => 'Partner',
        ]);
    }

    public static function salesProjects(): array
    {
        return SalesProject::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    public static function salesProjectLabel(?string $key): string
    {
        if (blank($key)) {
            return '-';
        }

        return SalesProject::withTrashed()->where('slug', $key)->value('name') ?: $key;
    }

    public static function labelFor(string $type, ?string $key): string
    {
        if (blank($key)) {
            return '-';
        }

        $options = match ($type) {
            'document_type' => self::documentTypes(),
            'issued_place' => self::issuedPlaces(),
            'department' => self::departments(),
            'employment_status' => self::employmentStatuses(),
            'office' => self::offices(),
            'contract_type' => self::contractTypes(),
            'sales_code' => self::salesCodes(),
            default => [],
        };

        return $options[$key] ?? $key;
    }

    public static function salesCodes(): array
    {
        $projectCodes = SalesProject::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (SalesProject $project): array => [
                $project->slug => $project->code_prefix ?: strtoupper(str_replace(['-', '_'], '', $project->slug)),
            ])
            ->all();

        return self::lookupOptions('sales_code', $projectCodes);
    }

    public static function roleUsers(string $role, ?int $zdId = null, ?int $amId = null): array
    {
        $query = User::role($role);

        if ($role === 'AM' && filled($zdId)) {
            $query->where('zd_id', $zdId);
        }

        if (in_array($role, ['Team Leader', 'Courier Manager'], true)) {
            if (filled($amId)) {
                $query->where('am_id', $amId);
            } elseif (filled($zdId)) {
                $query->where('zd_id', $zdId);
            }
        }

        return $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function managerChainFor(?int $userId): array
    {
        $empty = [
            'zd_id' => null,
            'am_id' => null,
            'team_leader_id' => null,
            'courier_manager_id' => null,
        ];

        if (blank($userId)) {
            return $empty;
        }

        $user = User::query()->find($userId);

        if (! $user instanceof User) {
            return $empty;
        }

        if ($user->hasRole('Team Leader')) {
            return [
                'zd_id' => $user->zd_id,
                'am_id' => $user->am_id,
                'team_leader_id' => $user->getKey(),
                'courier_manager_id' => null,
            ];
        }

        if ($user->hasRole('Courier Manager')) {
            return [
                'zd_id' => $user->zd_id,
                'am_id' => $user->am_id,
                'team_leader_id' => null,
                'courier_manager_id' => $user->getKey(),
            ];
        }

        if ($user->hasRole('AM')) {
            return [
                'zd_id' => $user->zd_id,
                'am_id' => $user->getKey(),
                'team_leader_id' => null,
                'courier_manager_id' => null,
            ];
        }

        if ($user->hasRole('ZD')) {
            return [
                'zd_id' => $user->getKey(),
                'am_id' => null,
                'team_leader_id' => null,
                'courier_manager_id' => null,
            ];
        }

        return $empty;
    }

    private static function lookupOptions(string $type, array $fallback): array
    {
        if (! Schema::hasTable('crm_lookups')) {
            return $fallback;
        }

        $options = CrmLookup::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('label', 'key')
            ->all();

        return $options ?: $fallback;
    }
}
