<?php

namespace App\Support\Permissions;

use App\Models\CrmModule;
use App\Models\Lead;
use App\Models\SalesProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadAccess
{
    public static function applyVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User || ! $user->can('lead.view')) {
            return $query->whereRaw('1 = 0');
        }

        self::applyRecordTypeScope($query);

        if (! $user->hasAnyRole(['Admin', 'Sales Admin'])) {
            self::applyProjectScope($query, $user);
        }

        return RecordVisibility::applyUserScope($query, $user, 'assigned_sale_id', 'assignedSale');
    }

    public static function canView(User $user, Lead $lead): bool
    {
        return $user->can('lead.view')
            && (self::isPromotedHotLead($lead) || self::canAccessLeadModule($user))
            && self::canAccessRecordProject($user, $lead)
            && RecordVisibility::canAccessUserOwnedRecord($user, $lead, 'assigned_sale_id', 'assignedSale');
    }

    public static function canUpdate(User $user, Lead $lead): bool
    {
        return $user->hasAnyRole(['Admin', 'Sales Admin'])
            && $user->can('lead.update')
            && self::canView($user, $lead);
    }

    public static function canProcess(User $user, Lead $lead): bool
    {
        if ($user->hasAnyRole(['Admin', 'Sales Admin'])) {
            return blank($lead->converted_at)
                && ! $lead->trashed();
        }

        return $user->can('lead.convert')
            && self::canView($user, $lead)
            && blank($lead->converted_at)
            && ! $lead->trashed()
            && ! in_array($lead->status, ['Từ chối', 'Khách hàng bị trùng'], true)
            && (
                $user->hasAnyRole(['Admin', 'Sales Admin'])
                || (int) $lead->assigned_sale_id === (int) $user->getKey()
                || ($user->hasRole('Courier Manager')
                    && (int) $lead->assignedSale?->courier_manager_id === (int) $user->getKey())
            );
    }

    public static function canConvert(User $user, Lead $lead): bool
    {
        return $user->can('lead.convert')
            && self::canView($user, $lead)
            && $lead->status === 'Khách hàng thoả mãn điều kiện'
            && blank($lead->converted_at)
            && ! $lead->trashed();
    }

    public static function canDelete(User $user, Lead $lead): bool
    {
        return $user->hasRole('Admin')
            && ! $lead->trashed();
    }

    public static function canAccessLeadModule(User $user): bool
    {
        $module = self::leadModule();

        return ! $module || SalesProjectAccess::canAccessModule($user, $module);
    }

    public static function hasActiveLeadProjects(): bool
    {
        return self::activeLeadProjectQuery()->exists();
    }

    public static function projectOptions(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $query = self::activeLeadProjectQuery();

        if (! $user->hasAnyRole(['Admin', 'Sales Admin'])) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);

            if ($slugs === []) {
                return [];
            }

            $query->whereIn('slug', $slugs);
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function defaultProjectId(?User $user): ?int
    {
        return null;
    }

    public static function canUseProjectId(?User $user, int|string|null $projectId): bool
    {
        if (! $user instanceof User || blank($projectId)) {
            return false;
        }

        $project = self::activeLeadProjectQuery()->find((int) $projectId);

        if (! $project instanceof SalesProject) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Sales Admin']) || SalesProjectAccess::canAccessProject($user, $project);
    }

    public static function normalizeProjectId(?User $user, int|string|null $projectId): ?int
    {
        return filled($projectId) ? (int) $projectId : null;
    }

    public static function selectedProjectSlug(int|string|null $projectId): ?string
    {
        if (blank($projectId)) {
            return null;
        }

        return SalesProject::query()->whereKey((int) $projectId)->value('slug');
    }

    public static function isPromotedHotLead(Lead $lead): bool
    {
        return $lead->salesProject?->slug === HotLeadAccess::PROJECT_SLUG
            && data_get($lead->payload, 'workflow.stage') === 'lead';
    }

    private static function applyProjectScope(Builder $query, User $user): void
    {
        $slugs = SalesProjectAccess::userProjectSlugs($user);

        $query->where(function (Builder $scope) use ($slugs): void {
            if ($slugs !== []) {
                $scope->whereHas('salesProject', fn (Builder $project): Builder => $project->whereIn('slug', $slugs));
            } else {
                $scope->whereRaw('1 = 0');
            }

            $scope->orWhere(function (Builder $hotLead): void {
                $hotLead
                    ->whereHas('salesProject', fn (Builder $project): Builder => $project->where('slug', HotLeadAccess::PROJECT_SLUG))
                    ->where('payload->workflow->stage', 'lead');
            });
        });
    }

    private static function canAccessRecordProject(User $user, Lead $lead): bool
    {
        if (self::isPromotedHotLead($lead)) {
            return true;
        }

        if (blank($lead->sales_project_id)) {
            return false;
        }

        $project = $lead->salesProject;

        return $project instanceof SalesProject
            && (bool) $project->is_active
            && ($user->hasAnyRole(['Admin', 'Sales Admin']) || SalesProjectAccess::canAccessProject($user, $project));
    }

    private static function applyRecordTypeScope(Builder $query): void
    {
        $query->where(function (Builder $scope): void {
            $scope
                ->whereHas('salesProject.crmModule', fn (Builder $module): Builder => $module->where('slug', 'applications'))
                ->orWhere(function (Builder $hotLead): void {
                    $hotLead
                        ->whereHas('salesProject', fn (Builder $project): Builder => $project->where('slug', HotLeadAccess::PROJECT_SLUG))
                        ->where('payload->workflow->stage', 'lead');
                });
        });
    }

    private static function activeLeadProjectQuery(): Builder
    {
        return SalesProject::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'acl-mix')
            ->whereHas('crmModule', fn (Builder $query): Builder => $query->where('slug', 'applications'))
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    private static function leadModule(): ?CrmModule
    {
        return CrmModule::query()->where('slug', 'leads')->first();
    }
}
