<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table): void {
            // Add missing fields from HyperLead postback
            if (!Schema::hasColumn('affiliate_conversions', 'shop_status_code')) {
                $table->string('shop_status_code')->nullable()->after('conversion_publisher_payout');
            }
            if (!Schema::hasColumn('affiliate_conversions', 'publisher_id')) {
                $table->string('publisher_id')->nullable()->after('shop_status_code');
            }
            if (!Schema::hasColumn('affiliate_conversions', 'conversion_date')) {
                $table->string('conversion_date')->nullable()->after('publisher_id');
            }
            if (!Schema::hasColumn('affiliate_conversions', 'conversion_modified_date')) {
                $table->string('conversion_modified_date')->nullable()->after('conversion_date');
            }
            if (!Schema::hasColumn('affiliate_conversions', 'click_date')) {
                $table->string('click_date')->nullable()->after('conversion_modified_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table): void {
            $columns = ['shop_status_code', 'publisher_id', 'conversion_date', 'conversion_modified_date', 'click_date'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('affiliate_conversions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
