<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feol_application_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('partner_lead_id')->nullable()->index();
            $table->string('partner_app_id')->nullable()->index();
            $table->string('main_status')->nullable()->index();
            $table->string('sub_status')->nullable()->index();
            $table->text('b1_url')->nullable();
            $table->text('deeplink_url')->nullable();
            $table->string('sync_state')->default('pending')->index();
            $table->timestamp('sync_requested_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('next_sync_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
        });

        $projectId = DB::table('sales_projects')->where('slug', 'fe-deeplink')->value('id');

        if ($projectId) {
            DB::table('applications')->where('sales_project_id', $projectId)->where('status', 'end')->update(['status' => 'pl_disbursed']);
            DB::table('applications')->where('sales_project_id', $projectId)->where('status', 'reject')->update(['status' => 'hard_reject']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feol_application_integrations');
    }
};
