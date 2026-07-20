<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_column_preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('table_key')->unique();
            $table->json('column_order')->nullable();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_column_preferences');
    }
};
