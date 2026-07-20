<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_lookups', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('key');
            $table->string('label');
            $table->string('value')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lookups');
    }
};
