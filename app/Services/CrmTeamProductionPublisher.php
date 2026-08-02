<?php

namespace App\Services;

use App\Models\CrmTeam;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class CrmTeamProductionPublisher
{
    /**
     * Module ownership columns that must receive the published Team snapshot.
     *
     * @var array<string, array<int, string>>
     */
    private const MODULE_OWNER_COLUMNS = [
        'leads' => ['team_leader_id', 'assigned_sale_id', 'created_by_id'],
        'applications' => ['team_leader_id', 'assigned_sale_id', 'created_by_id'],
        'data_center_leads' => ['team_leader_id', 'assigned_user_id', 'created_by_id'],
        'sale_profiles' => ['sale_owner_id', 'processing_owner_id'],
        'project_reports' => ['team_leader_id', 'created_by_id'],
    ];

    public function publish(CrmTeam $team): bool
    {
        if (! config('crm.team_publication.enabled')) {
            return false;
        }

        if (app()->runningUnitTests()) {
            throw new LogicException('Không được phát hành Team khi đang chạy test.');
        }

        if (! app()->environment('uat')) {
            throw new LogicException('Chỉ môi trường UAT được phép phát hành cấu hình Team.');
        }

        $connectionName = trim((string) config('crm.team_publication.connection'));

        if ($connectionName === '') {
            throw new RuntimeException('Chưa cấu hình kết nối phát hành Team.');
        }

        $destination = DB::connection($connectionName);
        $this->ensureSeparateDatabase($destination);

        $team->loadMissing([
            'manager:id,employee_code,am_id,zd_id',
            'members:id,employee_code',
        ]);

        $managerCode = trim((string) $team->manager?->employee_code);

        if ($managerCode === '') {
            throw new RuntimeException('Trưởng Team phải có mã nhân viên trước khi đồng bộ Prod.');
        }

        $memberCodes = $team->members
            ->map(fn ($member): string => trim((string) $member->employee_code))
            ->filter()
            ->unique()
            ->values();

        if ($memberCodes->count() !== $team->members->count()) {
            throw new RuntimeException('Mọi thành viên Team phải có mã nhân viên trước khi đồng bộ Prod.');
        }

        $requiredCodes = $memberCodes->concat([$managerCode])->unique()->values();

        $productionUsers = $destination
            ->table('users')
            ->whereIn('employee_code', $requiredCodes->all())
            ->get(['id', 'employee_code', 'am_id', 'zd_id'])
            ->keyBy(fn (object $user): string => (string) $user->employee_code);

        $missingCodes = $requiredCodes->diff($productionUsers->keys());

        if ($missingCodes->isNotEmpty()) {
            throw new RuntimeException(
                'Prod chưa có mã nhân viên: '.$missingCodes->implode(', '),
            );
        }

        $manager = $productionUsers->get($managerCode);
        $desiredMemberIds = $memberCodes
            ->map(fn (string $code): int => (int) $productionUsers->get($code)->id)
            ->all();

        $destination->transaction(function () use (
            $destination,
            $desiredMemberIds,
            $manager,
            $team,
        ): void {
            $teamId = (int) $team->getKey();

            $sameCode = $destination
                ->table('crm_teams')
                ->where('code', $team->code)
                ->where('id', '!=', $teamId)
                ->first(['id']);

            if ($sameCode) {
                throw new RuntimeException(
                    "Mã Team {$team->code} đang thuộc Team khác trên Prod.",
                );
            }

            $existingTeam = $destination
                ->table('crm_teams')
                ->where('id', $teamId)
                ->first(['id']);

            $timestamp = now();
            $teamValues = [
                'name' => $team->name,
                'code' => $team->code,
                'manager_id' => (int) $manager->id,
                'is_active' => (bool) $team->is_active,
                'updated_at' => $timestamp,
            ];

            if (! $existingTeam) {
                $teamValues['created_at'] = $team->created_at ?: $timestamp;
            }

            $destination
                ->table('crm_teams')
                ->updateOrInsert(['id' => $teamId], $teamValues);

            $currentMemberIds = $destination
                ->table('users')
                ->where('team_id', $teamId)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $removedMemberIds = array_values(array_diff(
                $currentMemberIds,
                $desiredMemberIds,
            ));

            if ($removedMemberIds !== []) {
                $destination
                    ->table('users')
                    ->whereIn('id', $removedMemberIds)
                    ->where('team_id', $teamId)
                    ->update([
                        'team_id' => null,
                        'team_leader_id' => null,
                        'am_id' => null,
                        'zd_id' => null,
                    ]);
            }

            if ($desiredMemberIds !== []) {
                $destination
                    ->table('users')
                    ->whereIn('id', $desiredMemberIds)
                    ->update([
                        'team_id' => $teamId,
                        'team_leader_id' => (int) $manager->id,
                        'am_id' => $manager->am_id,
                        'zd_id' => $manager->zd_id,
                    ]);
            }

            $this->syncModuleRecords(
                $destination,
                $teamId,
                (int) $manager->id,
                $desiredMemberIds,
                $removedMemberIds,
            );

            $destination->statement(
                "select setval(pg_get_serial_sequence('crm_teams', 'id'), ".
                'coalesce((select max(id) from crm_teams), 1), true)',
            );
        });

        return true;
    }

    private function ensureSeparateDatabase(Connection $destination): void
    {
        $sourceDatabase = DB::selectOne('select current_database() as name')?->name;
        $destinationDatabase = $destination
            ->selectOne('select current_database() as name')?->name;

        if (! $sourceDatabase || ! $destinationDatabase) {
            throw new RuntimeException('Không xác định được database nguồn hoặc đích.');
        }

        if ($sourceDatabase === $destinationDatabase) {
            throw new LogicException('Database UAT và Prod của Team không được trùng nhau.');
        }
    }

    /**
     * @param  array<int, int>  $desiredMemberIds
     * @param  array<int, int>  $removedMemberIds
     */
    private function syncModuleRecords(
        Connection $destination,
        int $teamId,
        int $managerId,
        array $desiredMemberIds,
        array $removedMemberIds,
    ): void {
        $schema = $destination->getSchemaBuilder();
        $assignedUserIds = array_values(array_unique([
            $managerId,
            ...$desiredMemberIds,
        ]));

        foreach (self::MODULE_OWNER_COLUMNS as $table => $ownerColumns) {
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, 'team_id')) {
                continue;
            }

            $availableColumns = array_values(array_filter(
                $ownerColumns,
                fn (string $column): bool => $schema->hasColumn($table, $column),
            ));

            if ($availableColumns === []) {
                continue;
            }

            if ($removedMemberIds !== []) {
                $destination
                    ->table($table)
                    ->where('team_id', $teamId)
                    ->where(fn (Builder $query) => $this->whereOwnerIn(
                        $query,
                        $availableColumns,
                        $removedMemberIds,
                    ))
                    ->update(['team_id' => null]);
            }

            $destination
                ->table($table)
                ->where(fn (Builder $query) => $this->whereOwnerIn(
                    $query,
                    $availableColumns,
                    $assignedUserIds,
                ))
                ->update(['team_id' => $teamId]);
        }
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, int>  $userIds
     */
    private function whereOwnerIn(
        Builder $query,
        array $columns,
        array $userIds,
    ): void {
        foreach ($columns as $index => $column) {
            $method = $index === 0 ? 'whereIn' : 'orWhereIn';
            $query->{$method}($column, $userIds);
        }
    }
}
