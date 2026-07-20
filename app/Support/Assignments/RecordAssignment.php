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
        return $actor instanceof User
            && $actor->hasRole('Admin')
            && ($record instanceof Lead || $record instanceof Application || $record instanceof SaleProfile);
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
        $project = $recordOrProject instanceof SalesProject ? $recordOrProject : self::projectFor($recordOrProject);
        $config = self::configFor($project);

        if ($config?->is_enabled && filled($config->user_ids)) {
            return $config->configuredUsers()
                ->when(filled($search), fn (Collection $users): Collection => $users
                    ->filter(fn (User $user): bool => str_contains(
                        mb_strtolower(implode(' ', [$user->name, $user->uid, $user->employee_code, $user->email])),
                        mb_strtolower((string) $search),
                    )))
                ->mapWithKeys(fn (User $user): array => [$user->getKey() => self::userLabel($user)])
                ->all();
        }

        return self::eligibleUsers($recordOrProject, includeAdmins: true, search: $search)
            ->mapWithKeys(fn (User $user): array => [$user->getKey() => self::userLabel($user)])
            ->all();
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

        if ($project?->slug === 'hot-lead') {
            return null;
        }

        $candidates = self::eligibleUsers($project, includeAdmins: false);

        if ($candidates->isEmpty()) {
            $candidates = self::eligibleUsers($project, includeAdmins: true);
        }

        if ($candidates->isEmpty() && $fallback instanceof User && self::isActive($fallback)) {
            return $fallback;
        }

        if ($candidates->isEmpty()) {
            return self::activeUsersQuery()->role('Admin')->orderBy('id')->first();
        }

        return self::leastLoadedUser($candidates);
    }

    public static function autoAssigneeForRecord(Model $record, ?User $fallback = null): ?User
    {
        return self::autoAssigneeForProject(self::projectFor($record), $fallback);
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

    private static function eligibleUsers(Model|SalesProject|null $recordOrProject, bool $includeAdmins, ?string $search = null): Collection
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
            ->filter(function (User $user) use ($slug, $includeAdmins): bool {
                if ($includeAdmins && $user->hasRole('Admin')) {
                    return true;
                }

                if (blank($slug)) {
                    return ! $user->hasRole('Admin');
                }

                return in_array($slug, $user->sales_projects ?? [], true);
            })
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
