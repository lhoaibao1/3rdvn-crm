<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            $table->string('attribution_param', 50)->default('aff_sub1')->after('tracking_url');
        });

        DB::table('affiliate_campaigns')->updateOrInsert(
            ['slug' => 'tinvay-vietcredit'],
            [
                'name' => 'TinVay - VietCredit',
                'logo_url' => null,
                'summary' => 'Đăng ký vay tiền mặt VietCredit',
                'details' => 'Link giới thiệu riêng được ghi nhận theo đúng nhân sự và tuyến quản lý.',
                'tracking_url' => 'https://fast.accesstrade.com.vn/deep_link/v6/5876543172579142727/6997817930567730686?sub4=oneatweb&url_enc=aHR0cHM6Ly90aW52YXkudmlldGNyZWRpdC5jb20udm4v',
                'attribution_param' => 'sub1',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('affiliate_campaigns')->where('slug', 'tinvay-vietcredit')->delete();

        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            $table->dropColumn('attribution_param');
        });
    }
};
