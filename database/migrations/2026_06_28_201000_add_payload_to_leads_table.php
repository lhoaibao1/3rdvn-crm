<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'payload')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->json('payload')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'payload')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('payload');
        });
    }
};
