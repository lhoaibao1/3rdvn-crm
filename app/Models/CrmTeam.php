<?php

namespace App\Models;

use App\Support\RoleHierarchy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmTeam extends Model
{
    protected $fillable = ['name', 'code', 'manager_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    /**
     * @param  array<int, int|string>  $memberIds
     */
    public function syncMembers(array $memberIds): void
    {
        DB::transaction(function () use ($memberIds): void {
            $memberIds = User::role(RoleHierarchy::SALES_ROLES)
                ->whereKey(collect($memberIds)->filter()->map(fn (mixed $id): int => (int) $id)->all())
                ->pluck('id')
                ->all();

            User::query()
                ->where('team_id', $this->getKey())
                ->when($memberIds !== [], fn ($query) => $query->whereNotIn('id', $memberIds))
                ->update([
                    'team_id' => null,
                    'team_leader_id' => null,
                    'am_id' => null,
                    'zd_id' => null,
                ]);

            $manager = $this->manager()->first();

            User::query()
                ->whereKey($memberIds)
                ->update([
                    'team_id' => $this->getKey(),
                    'team_leader_id' => $manager?->getKey(),
                    'am_id' => $manager?->am_id,
                    'zd_id' => $manager?->zd_id,
                ]);

            $this->syncModuleRecords($memberIds, $manager);
        });
    }

    private function syncModuleRecords(array $memberIds, ?User $manager): void
    {
        $userIds = collect($memberIds)
            ->push($manager?->getKey())
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->all();

        $tables = [
            'leads' => ['team_leader_id', 'assigned_sale_id', 'created_by_id'],
            'applications' => ['team_leader_id', 'assigned_sale_id', 'created_by_id'],
            'data_center_leads' => ['team_leader_id', 'assigned_user_id', 'created_by_id'],
            'sale_profiles' => [null, 'sale_owner_id', 'processing_owner_id'],
            'project_reports' => ['team_leader_id', 'created_by_id'],
        ];

        foreach ($tables as $table => $columns) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'team_id')) {
                continue;
            }

            DB::table($table)
                ->where(function ($query) use ($columns, $manager, $userIds): void {
                    $hasCondition = false;

                    foreach ($columns as $column) {
                        if (! $column || ! Schema::hasColumn($query->from, $column)) {
                            continue;
                        }

                        if ($column === 'team_leader_id' && $manager) {
                            $query->{$hasCondition ? 'orWhere' : 'where'}($column, $manager->getKey());
                            $hasCondition = true;
                        } elseif ($userIds !== []) {
                            $query->{$hasCondition ? 'orWhereIn' : 'whereIn'}($column, $userIds);
                            $hasCondition = true;
                        }
                    }

                    if (! $hasCondition) {
                        $query->whereRaw('1 = 0');
                    }
                })
                ->update(['team_id' => $this->getKey()]);
        }
    }
}
