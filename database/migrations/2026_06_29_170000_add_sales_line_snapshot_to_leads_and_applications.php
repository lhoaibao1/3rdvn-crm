<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table): void {
                if (! Schema::hasColumn('leads', 'created_by_id')) {
                    $table->foreignId('created_by_id')->nullable()->after('assigned_sale_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'team_leader_id')) {
                    $table->foreignId('team_leader_id')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'am_id')) {
                    $table->foreignId('am_id')->nullable()->after('team_leader_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'zd_id')) {
                    $table->foreignId('zd_id')->nullable()->after('am_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('applications')) {
            Schema::table('applications', function (Blueprint $table): void {
                if (! Schema::hasColumn('applications', 'created_by_id')) {
                    $table->foreignId('created_by_id')->nullable()->after('assigned_sale_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('applications', 'team_id')) {
                    $table->foreignId('team_id')->nullable()->after('created_by_id')->constrained('crm_teams')->nullOnDelete();
                }
                if (! Schema::hasColumn('applications', 'team_leader_id')) {
                    $table->foreignId('team_leader_id')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('applications', 'am_id')) {
                    $table->foreignId('am_id')->nullable()->after('team_leader_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('applications', 'zd_id')) {
                    $table->foreignId('zd_id')->nullable()->after('am_id')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applications')) {
            Schema::table('applications', function (Blueprint $table): void {
                foreach (['zd_id', 'am_id', 'team_leader_id', 'team_id', 'created_by_id'] as $column) {
                    if (Schema::hasColumn('applications', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table): void {
                foreach (['zd_id', 'am_id', 'team_leader_id', 'created_by_id'] as $column) {
                    if (Schema::hasColumn('leads', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }
    }
};
