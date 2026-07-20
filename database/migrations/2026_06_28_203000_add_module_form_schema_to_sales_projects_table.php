<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_projects') || Schema::hasColumn('sales_projects', 'module_form_schema')) {
            return;
        }

        Schema::table('sales_projects', function (Blueprint $table): void {
            $table->json('module_form_schema')->nullable()->after('lead_form_schema');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_projects') || ! Schema::hasColumn('sales_projects', 'module_form_schema')) {
            return;
        }

        Schema::table('sales_projects', function (Blueprint $table): void {
            $table->dropColumn('module_form_schema');
        });
    }
};
