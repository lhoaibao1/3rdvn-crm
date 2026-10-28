<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $combined = DB::table('affiliate_campaigns')->where('slug', 'shinhan-finance')->first();
        if (! $combined) {
            return;
        }

        DB::table('affiliate_campaigns')->where('id', $combined->id)->update([
            'name' => 'Shinhan Finance Android',
            'slug' => 'shinhan-finance-android',
            'summary' => 'Vay Tài Tốc online 100% trên iShinhan dành cho Android',
            'tracking_url_ios' => null,
            'sort_order' => 50,
            'updated_at' => now(),
        ]);

        DB::table('affiliate_campaigns')->updateOrInsert(
            ['slug' => 'shinhan-finance-ios'],
            [
                'name' => 'Shinhan Finance iOS',
                'logo_url' => 'https://pub2-aff.3rdvn.io.vn/static/logo-shinhan.svg',
                'summary' => 'Vay Tài Tốc online 100% trên iShinhan dành cho iPhone/iPad',
                'details' => $combined->details,
                'tracking_url' => $combined->tracking_url_ios,
                'tracking_url_ios' => null,
                'attribution_param' => 'sub1',
                'is_active' => true,
                'sort_order' => 51,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $ios = DB::table('affiliate_campaigns')->where('slug', 'shinhan-finance-ios')->first();
        DB::table('affiliate_campaigns')->where('slug', 'shinhan-finance-android')->update([
            'name' => 'Shinhan Finance',
            'slug' => 'shinhan-finance',
            'summary' => 'Vay Tài Tốc online 100% trên ứng dụng iShinhan',
            'tracking_url_ios' => $ios?->tracking_url,
            'updated_at' => now(),
        ]);
        DB::table('affiliate_campaigns')->where('slug', 'shinhan-finance-ios')->delete();
    }
};
