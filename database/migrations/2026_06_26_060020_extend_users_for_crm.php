<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('users', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('employee_code')->constrained('crm_teams')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('team_id');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('avatar_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['phone', 'avatar_path', 'team_id', 'employee_code'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
