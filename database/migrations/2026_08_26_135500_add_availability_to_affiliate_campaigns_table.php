<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            $table->timestamp('opens_at')->nullable()->after('is_active')->index();
            $table->timestamp('closes_at')->nullable()->after('opens_at')->index();
            $table->text('closure_message')->nullable()->after('closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_campaigns', function (Blueprint $table): void {
            $table->dropIndex(['opens_at']);
            $table->dropIndex(['closes_at']);
            $table->dropColumn(['opens_at', 'closes_at', 'closure_message']);
        });
    }
};
