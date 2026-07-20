<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
