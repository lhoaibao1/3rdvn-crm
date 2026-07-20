<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ui_settings', function (Blueprint $table) {
            $columns = [
                'smtp_enabled' => fn () => $table->boolean('smtp_enabled')->default(false),
                'smtp_host' => fn () => $table->string('smtp_host')->nullable(),
                'smtp_port' => fn () => $table->integer('smtp_port')->nullable(),
                'smtp_encryption' => fn () => $table->string('smtp_encryption')->nullable(),
                'smtp_username' => fn () => $table->string('smtp_username')->nullable(),
                'smtp_password' => fn () => $table->text('smtp_password')->nullable(),
                'mail_from_address' => fn () => $table->string('mail_from_address')->nullable(),
                'mail_from_name' => fn () => $table->string('mail_from_name')->nullable(),
                'password_reset_mail_subject' => fn () => $table->string('password_reset_mail_subject')->nullable(),
                'password_reset_mail_body' => fn () => $table->text('password_reset_mail_body')->nullable(),
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
                'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
                'mail_from_address', 'mail_from_name', 'password_reset_mail_subject', 'password_reset_mail_body',
            ] as $column) {
                if (Schema::hasColumn('ui_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
