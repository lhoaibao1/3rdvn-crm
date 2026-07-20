<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_projects')) {
            return;
        }

        Schema::create('sales_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_module_id')->nullable()->constrained('crm_modules')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code_prefix', 40)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['crm_module_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_projects');
    }
};
