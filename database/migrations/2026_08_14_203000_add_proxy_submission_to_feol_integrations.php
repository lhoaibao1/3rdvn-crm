<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('feol_application_integrations', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable()->unique();
            $table->string('partner_request_id', 100)->nullable()->unique();
            $table->string('submit_state')->default('awaiting_customer')->index();
            $table->unsignedSmallInteger('submit_attempts')->default(0);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('partner_last_attempt_at')->nullable();
            $table->timestamp('partner_submitted_at')->nullable()->index();
            $table->string('submit_ip', 45)->nullable();
            $table->text('submit_user_agent')->nullable();
            $table->text('submit_last_error')->nullable();
            $table->json('partner_submit_response')->nullable();
        });

        DB::table('feol_application_integrations')
            ->whereNull('public_token')
            ->orderBy('id')
            ->eachById(function (object $integration): void {
                DB::table('feol_application_integrations')
                    ->where('id', $integration->id)
                    ->update([
                        'public_token' => Str::random(48),
                        'partner_request_id' => 'FEDL-'.$integration->application_id.'-'.Str::upper(Str::random(12)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('feol_application_integrations', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropUnique(['partner_request_id']);
            $table->dropColumn([
                'public_token',
                'partner_request_id',
                'submit_state',
                'submit_attempts',
                'consented_at',
                'partner_last_attempt_at',
                'partner_submitted_at',
                'submit_ip',
                'submit_user_agent',
                'submit_last_error',
                'partner_submit_response',
            ]);
        });
    }
};
