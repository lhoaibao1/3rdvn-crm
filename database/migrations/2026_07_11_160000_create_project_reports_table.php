<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_project_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->string('customer_name');
            $table->string('province_code', 30);
            $table->string('province_name');
            $table->string('district_code', 30);
            $table->string('district_name');
            $table->string('identity_number', 30);
            $table->string('phone', 30);
            $table->string('product_code', 120);
            $table->string('product_name', 500);
            $table->unsignedBigInteger('loan_amount');
            $table->string('sales_code', 120);
            $table->string('status', 50)->default('Chờ xử lý');
            $table->foreignId('status_updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            $table->index(['sales_project_id', 'status', 'created_at']);
            $table->index(['created_by_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reports');
    }
};
