<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ui_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('ui_settings', 'mail_module_title')) {
                $table->string('mail_module_title')->default('Mail');
            }
            if (! Schema::hasColumn('ui_settings', 'mail_module_subtitle')) {
                $table->string('mail_module_subtitle')->nullable();
            }
            if (! Schema::hasColumn('ui_settings', 'mail_module_accent')) {
                $table->string('mail_module_accent', 20)->default('#2563eb');
            }
            if (! Schema::hasColumn('ui_settings', 'mail_show_user_meta')) {
                $table->boolean('mail_show_user_meta')->default(true);
            }
            if (! Schema::hasColumn('ui_settings', 'mail_compact_mode')) {
                $table->boolean('mail_compact_mode')->default(false);
            }
            if (! Schema::hasColumn('ui_settings', 'mail_user_meta_fields')) {
                $table->json('mail_user_meta_fields')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_settings', function (Blueprint $table): void {
            $columns = [
                'mail_module_title',
                'mail_module_subtitle',
                'mail_module_accent',
                'mail_show_user_meta',
                'mail_compact_mode',
                'mail_user_meta_fields',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ui_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
