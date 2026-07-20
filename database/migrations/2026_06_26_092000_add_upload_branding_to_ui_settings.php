<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ui_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('ui_settings', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('logo_text');
            }

            if (! Schema::hasColumn('ui_settings', 'favicon_path')) {
                $table->string('favicon_path')->nullable()->after('logo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_settings', function (Blueprint $table) {
            if (Schema::hasColumn('ui_settings', 'favicon_path')) {
                $table->dropColumn('favicon_path');
            }

            if (Schema::hasColumn('ui_settings', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
