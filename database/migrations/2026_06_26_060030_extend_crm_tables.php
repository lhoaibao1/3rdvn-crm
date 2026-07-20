<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('assigned_sale_id')->constrained('crm_teams')->nullOnDelete();
            }
            if (! Schema::hasColumn('leads', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('sale_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_profiles', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('sale_owner_id')->constrained('crm_teams')->nullOnDelete();
            }
            if (! Schema::hasColumn('sale_profiles', 'processing_owner_id')) {
                $table->foreignId('processing_owner_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('sale_profiles', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('processing_status');
            }
            if (! Schema::hasColumn('sale_profiles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('api_mappings', function (Blueprint $table) {
            if (! Schema::hasColumn('api_mappings', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('sale_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('sale_profiles', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('api_mappings', function (Blueprint $table) {
            if (Schema::hasColumn('api_mappings', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
