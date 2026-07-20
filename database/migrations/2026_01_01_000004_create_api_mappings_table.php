<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('api_mappings', function (Blueprint $table) {
   $table->id(); $table->string('mapping_name'); $table->string('target_system'); $table->string('endpoint_url')->nullable();
   $table->string('method')->default('POST'); $table->string('auth_type')->default('None'); $table->json('request_headers_json')->nullable();
   $table->json('field_mapping_json')->nullable(); $table->boolean('is_active')->default(false); $table->text('note')->nullable(); $table->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('api_mappings'); }
};
