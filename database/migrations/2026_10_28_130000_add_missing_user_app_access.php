<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'allowed_apps')) {
            Schema::table('users', fn (Blueprint $table) => $table->json('allowed_apps')->nullable());
        }
    }

    public function down(): void
    {
        // This may predate this migration on restored databases; preserve access data.
    }
};
