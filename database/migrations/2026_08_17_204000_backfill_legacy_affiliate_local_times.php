<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('affiliate_conversions')
            ->where('created_at', '<=', '2026-08-17 21:00:00')
            ->update([
                'click_time' => DB::raw("click_time + interval '7 hours'"),
                'conversion_time' => DB::raw("conversion_time + interval '7 hours'"),
                'conversion_modified_time' => DB::raw("conversion_modified_time + interval '7 hours'"),
                'conversion_status_updated_time' => DB::raw("conversion_status_updated_time + interval '7 hours'"),
            ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('affiliate_conversions')
            ->where('created_at', '<=', '2026-08-17 21:00:00')
            ->update([
                'click_time' => DB::raw("click_time - interval '7 hours'"),
                'conversion_time' => DB::raw("conversion_time - interval '7 hours'"),
                'conversion_modified_time' => DB::raw("conversion_modified_time - interval '7 hours'"),
                'conversion_status_updated_time' => DB::raw("conversion_status_updated_time - interval '7 hours'"),
            ]);
    }
};
