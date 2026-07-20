<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'sales_project_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('sales_project_id')
                ->nullable()
                ->after('id')
                ->constrained('sales_projects')
                ->nullOnDelete();

            $table->index(['sales_project_id', 'status'], 'leads_sales_project_status_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'sales_project_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_sales_project_status_index');
            $table->dropConstrainedForeignId('sales_project_id');
        });
    }
};
