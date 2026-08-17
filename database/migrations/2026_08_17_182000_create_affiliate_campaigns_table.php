<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('summary')->nullable();
            $table->text('details')->nullable();
            $table->text('tracking_url');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('affiliate_campaigns')->insert([
            'name' => 'SHB Finance', 'slug' => 'shb-finance',
            'logo_url' => 'https://riofin.asia/favicon.ico',
            'summary' => 'Vay tiêu dùng trực tuyến',
            'details' => 'Chia sẻ link giới thiệu riêng để ghi nhận kết quả theo đúng nhân sự và tuyến quản lý.',
            'tracking_url' => 'https://riofin.asia/v2/h6ZUoKMr6OVLqyCgJ9UNQkEnUZFMnjA2D_Pt6iQOrjw?lp=shbfinance',
            'is_active' => true, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_campaigns');
    }
};
