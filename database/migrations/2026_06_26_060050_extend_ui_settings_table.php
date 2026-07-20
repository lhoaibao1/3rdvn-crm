<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ui_settings', function (Blueprint $table) {
            $columns = [
                'secondary_color' => fn () => $table->string('secondary_color')->default('#64748b'),
                'sidebar_color' => fn () => $table->string('sidebar_color')->default('#ffffff'),
                'sidebar_active_color' => fn () => $table->string('sidebar_active_color')->default('#2563eb'),
                'text_color' => fn () => $table->string('text_color')->default('#101828'),
                'muted_text_color' => fn () => $table->string('muted_text_color')->default('#667085'),
                'font_family' => fn () => $table->string('font_family')->default('Inter, ui-sans-serif, system-ui'),
                'radius' => fn () => $table->integer('radius')->default(14),
                'density' => fn () => $table->string('density')->default('comfortable'),
                'login_background_type' => fn () => $table->string('login_background_type')->default('solid'),
                'login_background_color' => fn () => $table->string('login_background_color')->default('#f7f8fb'),
                'login_background_image' => fn () => $table->string('login_background_image')->nullable(),
                'login_layout' => fn () => $table->string('login_layout')->default('split'),
                'sidebar_width' => fn () => $table->integer('sidebar_width')->default(260),
                'sidebar_collapsed_width' => fn () => $table->integer('sidebar_collapsed_width')->default(76),
                'sidebar_default_collapsed' => fn () => $table->boolean('sidebar_default_collapsed')->default(false),
                'sidebar_style' => fn () => $table->string('sidebar_style')->default('light'),
                'topbar_height' => fn () => $table->integer('topbar_height')->default(72),
                'topbar_sticky' => fn () => $table->boolean('topbar_sticky')->default(true),
                'show_search' => fn () => $table->boolean('show_search')->default(true),
                'show_notifications' => fn () => $table->boolean('show_notifications')->default(false),
                'show_user_role' => fn () => $table->boolean('show_user_role')->default(true),
                'show_employee_code' => fn () => $table->boolean('show_employee_code')->default(true),
                'dashboard_layout' => fn () => $table->string('dashboard_layout')->default('default'),
                'dashboard_widgets' => fn () => $table->json('dashboard_widgets')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('ui_settings', $column)) {
                    $definition();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ui_settings', function (Blueprint $table) {
            foreach ([
                'secondary_color', 'sidebar_color', 'sidebar_active_color', 'text_color', 'muted_text_color',
                'font_family', 'radius', 'density', 'login_background_type', 'login_background_color',
                'login_background_image', 'login_layout', 'sidebar_width', 'sidebar_collapsed_width',
                'sidebar_default_collapsed', 'sidebar_style', 'topbar_height', 'topbar_sticky',
                'show_search', 'show_notifications', 'show_user_role', 'show_employee_code',
                'dashboard_layout', 'dashboard_widgets',
            ] as $column) {
                if (Schema::hasColumn('ui_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
