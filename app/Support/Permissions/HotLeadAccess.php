<?php

namespace App\Support\Permissions;

use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class HotLeadAccess
{
    public const PROJECT_SLUG = 'hot-lead';

    public static function project(): ?SalesProject
    {
        return SalesProject::query()
            ->where('slug', self::PROJECT_SLUG)
            ->where('is_active', true)
            ->first();
    }

    public static function canAccessModule(?User $user): bool
    {
        $project = self::project();

        return $user instanceof User
            && $project instanceof SalesProject
            && $user->can('hot_lead.view')
            && SalesProjectAccess::canAccessProject($user, $project);
    }

    public static function canCreate(?User $user): bool
    {
        return $user instanceof User
            && $user->can('hot_lead.create')
            && self::canAccessModule($user);
    }

    public static function canView(?User $user, Lead $lead): bool
    {
        return self::canAccessModule($user)
            && self::isHotLead($lead)
            && self::canAccessRecord($user, $lead);
    }

    public static function canProcess(?User $user, Lead $lead): bool
    {
        return self::canView($user, $lead)
            && ! $lead->trashed()
            && blank($lead->converted_sale_profile_id)
            && blank($lead->converted_at)
            && ! in_array($lead->status, ['Từ chối', 'Khách hàng bị trùng'], true)
            && ($user?->hasAnyRole(['Admin', 'Sales Admin']) || (int) $lead->assigned_sale_id === (int) $user?->getKey());
    }

    public static function applyVisibleTo(Builder $query, ?User $user): Builder
    {
        $query->whereHas('salesProject', fn (Builder $project): Builder => $project->where('slug', self::PROJECT_SLUG));
        $query->where(function (Builder $stage): void {
            $stage->whereNull('payload->workflow->stage')
                ->orWhere('payload->workflow->stage', '!=', 'lead');
        });

        if (! self::canAccessModule($user)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user?->hasAnyRole(['Admin', 'Sales Admin'])) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($user): void {
            $scope->where('created_by_id', $user->getKey())
                ->orWhere('assigned_sale_id', $user->getKey())
                ->orWhere('team_leader_id', $user->getKey())
                ->orWhere('am_id', $user->getKey())
                ->orWhere('zd_id', $user->getKey());
        });
    }

    public static function isHotLead(Lead $lead): bool
    {
        return self::isPendingHotLead($lead);
    }

    public static function isPendingHotLead(Lead $lead): bool
    {
        return $lead->salesProject?->slug === self::PROJECT_SLUG
            && data_get($lead->payload, 'workflow.stage') !== 'lead';
    }

    private static function canAccessRecord(?User $user, Lead $lead): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return true;
        }

        return (int) $lead->created_by_id === (int) $user->getKey()
            || (int) $lead->assigned_sale_id === (int) $user->getKey()
            || (int) $lead->team_leader_id === (int) $user->getKey()
            || (int) $lead->am_id === (int) $user->getKey()
            || (int) $lead->zd_id === (int) $user->getKey();
    }
}
