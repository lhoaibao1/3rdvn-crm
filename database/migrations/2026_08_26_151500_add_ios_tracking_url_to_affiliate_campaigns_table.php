<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            $table->text('tracking_url_ios')->nullable()->after('tracking_url');
        });

        DB::table('affiliate_campaigns')->updateOrInsert(
            ['slug' => 'shinhan-finance'],
            [
                'name' => 'Shinhan Finance',
                'logo_url' => 'https://pub2-aff.3rdvn.io.vn/static/logo-shinhan.svg',
                'summary' => 'Vay Tài Tốc online 100% trên ứng dụng iShinhan',
                'details' => 'Giải pháp vay tiêu dùng tự động, nhanh gọn và an toàn. Khách hàng cần nhập mã giới thiệu ACT23 trong ứng dụng để được ghi nhận.',
                'tracking_url' => 'https://fast.accesstrade.com.vn/deep_link/v6/5876543172579142727/6949942463850829113?sub4=oneatweb&url_enc=aHR0cHM6Ly9hcHAuYXBwc2ZseWVyLmNvbS92bi5jb20uc2hpbmhhbmZpbmFuY2UuaXNoaW5oYW4%2FcGlkPWFjY2Vzc3RyYWRldm5faW50JmFmX3NpdGVpZD17cHNufSZhZl9jX2lkPXtjYW1wYWlnbn0mYWZfY2xpY2tfbG9va2JhY2s9N2QmY2xpY2tpZD17Y2xpY2tpZH0%3D',
                'tracking_url_ios' => 'https://go.isclix.com/deep_link/v6/5876543172579142727/6949939948611548600?sub4=oneatweb&url_enc=aHR0cHM6Ly9hcHAuYXBwc2ZseWVyLmNvbS9pZDY3MzgxMTg3Njc%2FcGlkPWFjY2Vzc3RyYWRldm5faW50JmFmX3NpdGVpZD17cHNufSZhZl9jX2lkPXtjYW1wYWlnbn0mYWZfY2xpY2tfbG9va2JhY2s9N2QmY2xpY2tpZD17Y2xpY2tpZH0%3D',
                'attribution_param' => 'sub1',
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('affiliate_campaigns')->where('slug', 'shinhan-finance')->delete();

        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            $table->dropColumn('tracking_url_ios');
        });
    }
};
