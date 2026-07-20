<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('route_name');
            $table->integer('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->json('required_permissions')->nullable();
            $table->json('required_roles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_modules');
    }
};
