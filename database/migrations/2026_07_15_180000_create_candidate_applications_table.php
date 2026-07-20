<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('application_code')->nullable()->unique();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 24);
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('applied_position');
            $table->string('current_position')->nullable();
            $table->string('latest_company')->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->string('education_level')->nullable();
            $table->unsignedBigInteger('expected_salary')->nullable();
            $table->date('available_from')->nullable();
            $table->text('address_line')->nullable();
            $table->string('province_code', 20)->nullable();
            $table->string('province_name')->nullable();
            $table->string('district_code', 20)->nullable();
            $table->string('district_name')->nullable();
            $table->string('ward_code', 20)->nullable();
            $table->string('ward_name')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path');
            $table->string('source')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->text('internal_note')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('converted_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('consented_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email', 'phone']);
            $table->index(['applied_position', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_applications');
    }
};
