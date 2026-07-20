<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ui_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('ui_settings', 'notification_sound')) {
                $table->string('notification_sound', 20)->default('outlook');
            }
            if (! Schema::hasColumn('ui_settings', 'notification_sound_path')) {
                $table->string('notification_sound_path')->nullable();
            }
            if (! Schema::hasColumn('ui_settings', 'notification_sound_volume')) {
                $table->unsignedSmallInteger('notification_sound_volume')->default(80);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_settings', function (Blueprint $table): void {
            foreach ([
                'notification_sound',
                'notification_sound_path',
                'notification_sound_volume',
            ] as $column) {
                if (Schema::hasColumn('ui_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
