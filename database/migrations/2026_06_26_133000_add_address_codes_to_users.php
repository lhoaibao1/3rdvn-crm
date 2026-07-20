<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'province_code')) {
                $table->string('province_code', 30)->nullable()->after('address_line');
            }
            if (! Schema::hasColumn('users', 'district_code')) {
                $table->string('district_code', 30)->nullable()->after('province_name');
            }
            if (! Schema::hasColumn('users', 'ward_code')) {
                $table->string('ward_code', 30)->nullable()->after('district_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['ward_code', 'district_code', 'province_code'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
