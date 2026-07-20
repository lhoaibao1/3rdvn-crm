<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leads', 'lead_code')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->string('lead_code')->nullable()->unique()->after('id');
            });
        }

        DB::table('leads')
            ->whereNull('lead_code')
            ->orderBy('id')
            ->select(['id', 'created_at'])
            ->chunkById(100, function ($leads): void {
                foreach ($leads as $index => $lead) {
                    $date = $lead->created_at ? \Illuminate\Support\Carbon::parse($lead->created_at)->format('ymd') : now()->format('ymd');
                    $code = 'LD'.$date.str_pad((string) $lead->id, 6, '0', STR_PAD_LEFT);

                    DB::table('leads')->where('id', $lead->id)->update(['lead_code' => $code]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'lead_code')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropUnique(['lead_code']);
                $table->dropColumn('lead_code');
            });
        }
    }
};
