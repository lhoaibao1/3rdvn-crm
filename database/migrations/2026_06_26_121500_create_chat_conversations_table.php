<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table): void {
                $table->id();
                $table->string('type')->default('group');
                $table->string('name')->nullable();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chat_conversation_user')) {
            Schema::create('chat_conversation_user', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('last_read_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
                $table->timestamp('last_read_at')->nullable();
                $table->timestamps();

                $table->unique(['chat_conversation_id', 'user_id'], 'chat_conversation_user_unique');
            });
        }

        if (Schema::hasTable('chat_messages') && (! Schema::hasColumn('chat_messages', 'chat_conversation_id'))) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->foreignId('chat_conversation_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->index(['chat_conversation_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages') && Schema::hasColumn('chat_messages', 'chat_conversation_id')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('chat_conversation_id');
            });
        }

        Schema::dropIfExists('chat_conversation_user');
        Schema::dropIfExists('chat_conversations');
    }
};
