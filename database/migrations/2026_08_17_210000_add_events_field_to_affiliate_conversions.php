<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table): void {
            // Add 'events' column if not exists (for better alignment with postback payload)
            if (!Schema::hasColumn('affiliate_conversions', 'events')) {
                $table->text('events')->nullable()->after('event');
            }
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table): void {
            if (Schema::hasColumn('affiliate_conversions', 'events')) {
                $table->dropColumn('events');
            }
        });
    }
};
