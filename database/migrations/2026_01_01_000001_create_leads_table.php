<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('leads', function (Blueprint $table) {
   $table->id(); $table->string('lead_name'); $table->string('phone')->nullable(); $table->string('email')->nullable();
   $table->string('source')->nullable(); $table->foreignId('assigned_sale_id')->nullable()->constrained('users')->nullOnDelete();
   $table->string('status')->default('Mới'); $table->text('note')->nullable(); $table->foreignId('converted_sale_profile_id')->nullable();
   $table->timestamp('converted_at')->nullable(); $table->foreignId('converted_by_id')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('leads'); }
};
