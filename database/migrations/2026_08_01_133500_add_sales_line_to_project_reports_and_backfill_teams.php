<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_reports')) {
            Schema::table('project_reports', function (Blueprint $table): void {
                if (! Schema::hasColumn('project_reports', 'team_id')) {
                    $table->foreignId('team_id')->nullable()->after('created_by_id')->constrained('crm_teams')->nullOnDelete();
                }

                if (! Schema::hasColumn('project_reports', 'team_leader_id')) {
                    $table->foreignId('team_leader_id')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('project_reports', 'am_id')) {
                    $table->foreignId('am_id')->nullable()->after('team_leader_id')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('project_reports', 'zd_id')) {
                    $table->foreignId('zd_id')->nullable()->after('am_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        $this->backfillExistingModules();
        $this->backfillProjectReports();
    }

    private function backfillExistingModules(): void
    {
        if (! Schema::hasTable('crm_teams') || ! Schema::hasTable('users')) {
            return;
        }

        $teamByManager = DB::table('crm_teams')
            ->whereNotNull('manager_id')
            ->pluck('id', 'manager_id')
            ->mapWithKeys(fn (mixed $teamId, mixed $managerId): array => [(int) $managerId => (int) $teamId])
            ->all();

        $teamByUser = DB::table('users')
            ->whereNotNull('team_id')
            ->pluck('team_id', 'id')
            ->mapWithKeys(fn (mixed $teamId, mixed $userId): array => [(int) $userId => (int) $teamId])
            ->all();

        $tables = [
            'leads' => ['leader' => 'team_leader_id', 'users' => ['assigned_sale_id', 'created_by_id']],
            'applications' => ['leader' => 'team_leader_id', 'users' => ['assigned_sale_id', 'created_by_id']],
            'data_center_leads' => ['leader' => 'team_leader_id', 'users' => ['assigned_user_id', 'created_by_id']],
            'sale_profiles' => ['leader' => null, 'users' => ['sale_owner_id', 'processing_owner_id']],
        ];

        foreach ($tables as $table => $mapping) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'team_id')) {
                continue;
            }

            $columns = array_values(array_filter([
                'id',
                $mapping['leader'],
                ...$mapping['users'],
            ]));

            DB::table($table)
                ->select($columns)
                ->whereNull('team_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table, $mapping, $teamByManager, $teamByUser): void {
                    foreach ($rows as $row) {
                        $teamId = null;
                        $leaderColumn = $mapping['leader'];

                        if ($leaderColumn && filled($row->{$leaderColumn} ?? null)) {
                            $teamId = $teamByManager[(int) $row->{$leaderColumn}] ?? null;
                        }

                        foreach ($mapping['users'] as $userColumn) {
                            if ($teamId || blank($row->{$userColumn} ?? null)) {
                                continue;
                            }

                            $teamId = $teamByUser[(int) $row->{$userColumn}] ?? null;
                        }

                        if ($teamId) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->whereNull('team_id')
                                ->update(['team_id' => $teamId]);
                        }
                    }
                });
        }
    }

    private function backfillProjectReports(): void
    {
        if (! Schema::hasTable('project_reports') || ! Schema::hasColumn('project_reports', 'team_id')) {
            return;
        }

        $users = DB::table('users')
            ->select(['id', 'team_id', 'team_leader_id', 'am_id', 'zd_id'])
            ->get()
            ->keyBy('id');

        $teamByManager = DB::table('crm_teams')
            ->whereNotNull('manager_id')
            ->pluck('id', 'manager_id')
            ->mapWithKeys(fn (mixed $teamId, mixed $managerId): array => [(int) $managerId => (int) $teamId])
            ->all();

        DB::table('project_reports')
            ->select(['id', 'application_id', 'created_by_id'])
            ->whereNull('team_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($users, $teamByManager): void {
                $applicationIds = collect($rows)
                    ->pluck('application_id')
                    ->filter()
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique()
                    ->all();

                $applications = DB::table('applications')
                    ->select(['id', 'team_id', 'team_leader_id', 'am_id', 'zd_id'])
                    ->whereIn('id', $applicationIds)
                    ->get()
                    ->keyBy('id');

                foreach ($rows as $row) {
                    $application = filled($row->application_id)
                        ? $applications->get((int) $row->application_id)
                        : null;
                    $creator = filled($row->created_by_id)
                        ? $users->get((int) $row->created_by_id)
                        : null;

                    $leaderId = $application?->team_leader_id
                        ?: $creator?->team_leader_id
                        ?: (isset($teamByManager[(int) ($creator?->id ?? 0)]) ? $creator?->id : null);
                    $teamId = $application?->team_id
                        ?: $creator?->team_id
                        ?: ($teamByManager[(int) ($leaderId ?? 0)] ?? null);

                    if (! $teamId) {
                        continue;
                    }

                    DB::table('project_reports')->where('id', $row->id)->update([
                        'team_id' => $teamId,
                        'team_leader_id' => $leaderId,
                        'am_id' => $application?->am_id ?: $creator?->am_id,
                        'zd_id' => $application?->zd_id ?: $creator?->zd_id,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_reports')) {
            return;
        }

        Schema::table('project_reports', function (Blueprint $table): void {
            foreach (['zd_id', 'am_id', 'team_leader_id', 'team_id'] as $column) {
                if (Schema::hasColumn('project_reports', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
