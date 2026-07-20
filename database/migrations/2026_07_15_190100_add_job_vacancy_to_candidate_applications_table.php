<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table): void {
            $table->foreignId('job_vacancy_id')
                ->nullable()
                ->after('application_code')
                ->constrained('job_vacancies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('job_vacancy_id');
        });
    }
};
