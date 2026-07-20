<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('applications')) {
            return;
        }

        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_project_id')->constrained('sales_projects')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('application_code')->nullable()->unique();
            $table->string('applicant_name');
            $table->string('phone', 30)->nullable();
            $table->string('identity_number', 50)->nullable();
            $table->string('status', 50)->default('processing');
            $table->foreignId('assigned_sale_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_project_id', 'status']);
            $table->index(['assigned_sale_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
