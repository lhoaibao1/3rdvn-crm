<?php

namespace App\Support\Assignments;

use App\Models\Application;
use App\Models\Lead;
use App\Models\ProcessingAssignmentConfig;
use App\Models\SaleProfile;
use App\Models\SalesProject;
use App\Models\User;
use App\Support\SalesLineSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RecordAssignment
{
    public static function canAssign(?User $actor, Model $record): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        if ($actor->hasRole('Admin')) {
            return $record instanceof Lead || $record instanceof Application || $record instanceof SaleProfile;
        }

        if (! $actor->hasRole('Courier Manager') || ! ($record instanceof Lead || $record instanceof Application)) {
            return false;
        }

        $record->loadMissing('assignedSale');

        return (int) $record->assigned_sale_id === (int) $actor->getKey()
            || (int) $record->assignedSale?->courier_manager_id === (int) $actor->getKey();
    }

    public static function currentAssigneeId(Model $record): ?int
    {
        return match (true) {
            $record instanceof SaleProfile => $record->processing_owner_id,
            $record instanceof Lead, $record instanceof Application => $record->assigned_sale_id,
            default => null,
        };
    }

    public static function assigneeOptions(Model|SalesProject|null $recordOrProject, ?string $search = null): array
    {
        $actor = auth()->user();

        if ($actor?->hasRole('Courier Manager')) {
            return self::eligibleUsers($recordOrProject, search: $search)
                ->filter(fn (User $user): bool => $user->hasRole('Courier')
                    && (int) $user->courier_manager_id === (int) $actor->getKey())
                ->mapWithKeys(fn (User $user): array => [$user->getKey() => self::userLabel($user)])
                ->all();
        }

        return self::eligibleUsers($recordOrProject, search: $search)
            ->mapWithKeys(fn (User $user): array => [$user->getKey() => self::userLabel($user)])
            ->all();
    }

    public static function canAssignTo(?User $actor, Model $record, User $assignee): bool
    {
        if (! self::canAssign($actor, $record)) {
            return false;
        }

        return array_key_exists($assignee->getKey(), self::assigneeOptions($record));
    }

    public static function autoAssigneeForProject(?SalesProject $project, ?User $fallback = null): ?User
    {
        $config = self::configFor($project);

        if ($config instanceof ProcessingAssignmentConfig) {
            if (! $config->is_enabled || ! $config->auto_assign) {
                return null;
            }

            $candidates = $config->configuredUsers();

            return $candidates->isEmpty() ? null : $candidates->random();
        }

        return null;
    }

    public static function autoAssigneeForRecord(Model $record, ?User $fallback = null): ?User
    {
        return self::autoAssigneeForProject(self::projectFor($record), $fallback);
    }

    public static function isEligibleForProject(User $user, SalesProject $project): bool
    {
        return self::isActive($user) && ProcessingAssignmentConfig::canReceiveProject($user, $project->slug);
    }

    public static function assign(Model $record, User $assignee): void
    {
        if ($record instanceof Lead) {
            $attributes = self::leadLikeAssignmentAttributes($assignee);
            $record->forceFill($attributes)->save();
            $record->loadMissing(['application', 'convertedSaleProfile']);

            if ($record->application instanceof Application) {
                $record->application->forceFill($attributes)->save();
            }

            if ($record->convertedSaleProfile instanceof SaleProfile) {
                $record->convertedSaleProfile->forceFill([
                    'processing_owner_id' => $assignee->getKey(),
                    'team_id' => $assignee->team_id,
                ])->save();
            }

            return;
        }

        if ($record instanceof Application) {
            $record->loadMissing('salesProject:id,slug');

            if ($record->salesProject?->slug === 'acl-mix') {
                $record->forceFill(['assigned_sale_id' => $assignee->getKey()])->save();

                return;
            }

            $record->forceFill(self::leadLikeAssignmentAttributes($assignee))->save();

            return;
        }

        if ($record instanceof SaleProfile) {
            $record->forceFill([
                'processing_owner_id' => $assignee->getKey(),
                'team_id' => $assignee->team_id,
            ])->save();
        }
    }

    public static function leadLikeAssignmentAttributes(User $assignee): array
    {
        $snapshot = SalesLineSnapshot::fromUser($assignee);
        unset($snapshot['created_by_id']);

        return $snapshot;
    }

    public static function recordLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Lead => $record->lead_code ?: 'Lead #'.$record->getKey(),
            $record instanceof Application => $record->application_code ?: 'Hồ sơ #'.$record->getKey(),
            $record instanceof SaleProfile => 'HS #'.$record->getKey(),
            default => '#'.$record->getKey(),
        };
    }

    private static function eligibleUsers(Model|SalesProject|null $recordOrProject, ?string $search = null): Collection
    {
        $project = $recordOrProject instanceof SalesProject ? $recordOrProject : self::projectFor($recordOrProject);
        $slug = $project?->slug;

        return self::activeUsersQuery()
            ->with('roles')
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('uid', 'ilike', "%{$search}%")
                        ->orWhere('employee_code', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => blank($slug)
                ? ! $user->hasRole('Admin')
                : ProcessingAssignmentConfig::canReceiveProject($user, $slug))
            ->values();
    }

    private static function leastLoadedUser(Collection $users): ?User
    {
        $ids = $users->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        if ($ids === []) {
            return null;
        }

        $leadCounts = Lead::query()
            ->whereIn('assigned_sale_id', $ids)
            ->selectRaw('assigned_sale_id, count(*) as aggregate')
            ->groupBy('assigned_sale_id')
            ->pluck('aggregate', 'assigned_sale_id');

        $applicationCounts = Application::query()
            ->whereIn('assigned_sale_id', $ids)
            ->selectRaw('assigned_sale_id, count(*) as aggregate')
            ->groupBy('assigned_sale_id')
            ->pluck('aggregate', 'assigned_sale_id');

        $profileCounts = SaleProfile::query()
            ->whereIn('processing_owner_id', $ids)
            ->selectRaw('processing_owner_id, count(*) as aggregate')
            ->groupBy('processing_owner_id')
            ->pluck('aggregate', 'processing_owner_id');

        return $users
            ->sortBy(fn (User $user): array => [
                (int) ($leadCounts[$user->getKey()] ?? 0)
                    + (int) ($applicationCounts[$user->getKey()] ?? 0)
                    + (int) ($profileCounts[$user->getKey()] ?? 0),
                $user->getKey(),
            ])
            ->first();
    }

    private static function projectFor(mixed $record): ?SalesProject
    {
        if ($record instanceof Lead || $record instanceof Application) {
            return $record->salesProject ?: SalesProject::query()->find($record->sales_project_id);
        }

        if ($record instanceof SaleProfile) {
            $record->loadMissing('sourceLead.salesProject');

            return $record->sourceLead?->salesProject;
        }

        return null;
    }

    private static function configFor(?SalesProject $project): ?ProcessingAssignmentConfig
    {
        if (! $project instanceof SalesProject) {
            return null;
        }

        return ProcessingAssignmentConfig::query()->where('sales_project_id', $project->getKey())->first();
    }

    private static function activeUsersQuery()
    {
        return User::query()->whereNotIn('employment_status', [
            'inactive',
            User::STATUS_DEACTIVE,
            'resigned',
            User::STATUS_DELETED,
        ]);
    }

    private static function isActive(User $user): bool
    {
        return ! in_array($user->employment_status, [
            'inactive',
            User::STATUS_DEACTIVE,
            'resigned',
            User::STATUS_DELETED,
        ], true);
    }

    private static function userLabel(User $user): string
    {
        return implode(' · ', array_filter([
            $user->name,
            $user->uid,
            $user->employee_code,
        ], fn (?string $value): bool => filled($value)));
    }
}
