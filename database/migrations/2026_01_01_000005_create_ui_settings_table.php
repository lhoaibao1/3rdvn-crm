<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('ui_settings', function (Blueprint $table) {
   $table->id(); $table->string('app_name')->default('3RDVN CRM'); $table->string('logo_text')->nullable(); $table->string('favicon_url')->nullable();
   $table->string('login_title')->nullable(); $table->text('login_subtitle')->nullable(); $table->string('primary_color')->default('#2563eb');
   $table->string('background_color')->default('#f7f8fb'); $table->string('surface_color')->default('#ffffff'); $table->string('border_color')->default('#e5e7eb');
   $table->integer('sidebar_width_expanded')->default(232); $table->integer('sidebar_width_collapsed')->default(68); $table->timestamps();
  });
  // Migrations must not hydrate the current model because later migrations
  // add many UiSetting columns that do not exist yet during a fresh install.
  DB::table('ui_settings')->insert([
   'app_name' => '3RDVN CRM',
   'logo_text' => '3RDVN CRM',
   'login_title' => 'Đăng nhập 3RDVN CRM',
   'login_subtitle' => 'Hệ thống CRM nội bộ',
   'primary_color' => '#2563eb',
   'background_color' => '#f7f8fb',
   'surface_color' => '#ffffff',
   'border_color' => '#e5e7eb',
   'sidebar_width_expanded' => 232,
   'sidebar_width_collapsed' => 68,
   'created_at' => now(),
   'updated_at' => now(),
  ]);
 }
 public function down(): void { Schema::dropIfExists('ui_settings'); }
};
