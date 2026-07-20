<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('mail_address')->nullable()->unique();
            $table->string('mail_account_id')->nullable()->unique();
            $table->string('mail_status')->default('not_created');
            $table->unsignedInteger('mail_quota_mb')->default(2048);
            $table->timestampTz('mail_provisioned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['mail_address']);
            $table->dropUnique(['mail_account_id']);
            $table->dropColumn([
                'mail_address',
                'mail_account_id',
                'mail_status',
                'mail_quota_mb',
                'mail_provisioned_at',
            ]);
        });
    }
};
