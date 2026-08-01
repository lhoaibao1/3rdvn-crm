<?php

namespace App\Models;

use App\Support\RoleHierarchy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        });
    }
}
