<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_projects') || Schema::hasColumn('sales_projects', 'workflow_schema')) {
            return;
        }

        Schema::table('sales_projects', function (Blueprint $table): void {
            $table->json('workflow_schema')->nullable()->after('module_form_schema');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_projects') || ! Schema::hasColumn('sales_projects', 'workflow_schema')) {
            return;
        }

        Schema::table('sales_projects', function (Blueprint $table): void {
            $table->dropColumn('workflow_schema');
        });
    }
};
