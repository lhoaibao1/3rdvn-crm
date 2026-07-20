<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('sale_profiles', function (Blueprint $table) {
   $table->id(); $table->string('customer_name'); $table->string('phone')->nullable(); $table->string('email')->nullable();
   $table->string('identity_number')->nullable(); $table->text('address')->nullable(); $table->string('product_interest')->nullable();
   $table->foreignId('sale_owner_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('status')->default('Nháp');
   $table->string('approval_status')->default('Chưa gửi'); $table->text('note')->nullable(); $table->foreignId('source_lead_id')->nullable()->constrained('leads')->nullOnDelete();
   $table->text('rejection_reason')->nullable(); $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
   $table->timestamp('approved_at')->nullable(); $table->string('processing_status')->nullable(); $table->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('sale_profiles'); }
};
