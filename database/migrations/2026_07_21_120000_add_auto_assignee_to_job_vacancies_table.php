<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table): void {
            $table->foreignId('auto_assignee_id')
                ->nullable()
                ->after('published_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('auto_assignee_id');
        });
    }
};
