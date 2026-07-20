<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('record_change_logs')) {
            return;
        }

        Schema::create('record_change_logs', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('record');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_change_logs');
    }
};
