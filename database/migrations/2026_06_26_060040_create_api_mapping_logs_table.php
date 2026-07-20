<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_mapping_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_mapping_id')->nullable()->constrained('api_mappings')->nullOnDelete();
            $table->foreignId('sale_profile_id')->nullable()->constrained('sale_profiles')->nullOnDelete();
            $table->string('target_system')->nullable();
            $table->string('endpoint_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('result')->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_mapping_logs');
    }
};
