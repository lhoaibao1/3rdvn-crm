<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\UiSetting;
return new class extends Migration {
 public function up(): void {
  Schema::create('ui_settings', function (Blueprint $table) {
   $table->id(); $table->string('app_name')->default('3RDVN CRM'); $table->string('logo_text')->nullable(); $table->string('favicon_url')->nullable();
   $table->string('login_title')->nullable(); $table->text('login_subtitle')->nullable(); $table->string('primary_color')->default('#2563eb');
   $table->string('background_color')->default('#f7f8fb'); $table->string('surface_color')->default('#ffffff'); $table->string('border_color')->default('#e5e7eb');
   $table->integer('sidebar_width_expanded')->default(232); $table->integer('sidebar_width_collapsed')->default(68); $table->timestamps();
  });
  UiSetting::query()->create(UiSetting::defaults());
 }
 public function down(): void { Schema::dropIfExists('ui_settings'); }
};
