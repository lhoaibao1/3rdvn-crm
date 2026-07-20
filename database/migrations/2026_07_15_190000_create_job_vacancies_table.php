<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_vacancies', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('slug')->nullable()->unique();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('work_location')->nullable();
            $table->string('employment_type', 40)->default('full_time');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('experience_level')->nullable();
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->boolean('salary_negotiable')->default(true);
            $table->date('application_deadline')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('contact_email')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'status', 'application_deadline'], 'job_vacancies_public_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_vacancies');
    }
};
