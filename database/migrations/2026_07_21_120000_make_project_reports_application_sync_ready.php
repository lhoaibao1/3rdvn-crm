<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_reports', function (Blueprint $table): void {
            $table->string('province_code', 30)->nullable()->change();
            $table->string('province_name')->nullable()->change();
            $table->string('district_code', 30)->nullable()->change();
            $table->string('district_name')->nullable()->change();
            $table->string('identity_number', 30)->nullable()->change();
            $table->string('phone', 30)->nullable()->change();
            $table->string('product_code', 120)->nullable()->change();
            $table->string('product_name', 500)->nullable()->change();
            $table->unsignedBigInteger('loan_amount')->nullable()->change();
            $table->string('sales_code', 120)->nullable()->change();
            $table->unsignedInteger('approved_months')->nullable();
            $table->decimal('approved_interest_rate', 8, 4)->nullable();
            $table->json('source_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_reports', function (Blueprint $table): void {
            $table->dropColumn(['approved_months', 'approved_interest_rate', 'source_data']);
        });
    }
};
