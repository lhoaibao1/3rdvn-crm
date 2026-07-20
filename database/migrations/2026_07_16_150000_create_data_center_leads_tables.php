<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_center_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('referral_code')->nullable()->unique();
            $table->string('customer_name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('identity_number', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('province_code', 30)->nullable();
            $table->string('province_name')->nullable();
            $table->string('district_code', 30)->nullable();
            $table->string('district_name')->nullable();
            $table->string('ward_code', 30)->nullable();
            $table->string('ward_name')->nullable();
            $table->string('source')->nullable();
            $table->string('status', 40)->default('pending');
            $table->text('call_note')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('crm_teams')->nullOnDelete();
            $table->foreignId('team_leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('am_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('zd_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assigned_user_id', 'status', 'created_at']);
            $table->index(['team_leader_id', 'am_id', 'zd_id']);
            $table->index(['phone', 'identity_number']);
        });

        Schema::create('data_center_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_center_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_project_id')->constrained()->restrictOnDelete();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->foreignId('converted_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('converted_at');
            $table->timestamps();

            $table->unique(['data_center_lead_id', 'sales_project_id'], 'dc_conversion_project_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_center_conversions');
        Schema::dropIfExists('data_center_leads');
    }
};
