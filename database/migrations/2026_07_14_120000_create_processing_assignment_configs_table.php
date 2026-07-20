<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_assignment_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_project_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('auto_assign')->default(false);
            $table->json('user_ids')->nullable();
            $table->json('statuses')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_assignment_configs');
    }
};
