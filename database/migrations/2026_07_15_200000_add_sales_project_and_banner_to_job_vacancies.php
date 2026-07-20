<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table): void {
            $table->foreignId('sales_project_id')
                ->nullable()
                ->after('slug')
                ->constrained('sales_projects')
                ->nullOnDelete();
            $table->string('banner_path')->nullable()->after('sales_project_id');
        });
    }

    public function down(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sales_project_id');
            $table->dropColumn('banner_path');
        });
    }
};
