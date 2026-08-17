<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table): void {
            $table->string('product_category_id')->nullable()->after('product_sku');
            $table->timestamp('conversion_status_updated_time')->nullable()->after('conversion_modified_time');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table): void {
            $table->dropColumn(['product_category_id', 'conversion_status_updated_time']);
        });
    }
};
