<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $projectId = DB::table('sales_projects')->where('slug', 'lotte-finance')->value('id');

        if (! $projectId) {
            return;
        }

        DB::table('applications')
            ->where('sales_project_id', $projectId)
            ->where('id', 55)
            ->where('status', 'processing')
            ->update(['status' => 'lotte_sale_completion', 'updated_at' => now()]);

        DB::table('applications')
            ->where('sales_project_id', $projectId)
            ->whereIn('id', [17, 20])
            ->where('status', 'rejected')
            ->update(['status' => 'lotte_rejected', 'updated_at' => now()]);
    }

    public function down(): void
    {
        $projectId = DB::table('sales_projects')->where('slug', 'lotte-finance')->value('id');

        if (! $projectId) {
            return;
        }

        DB::table('applications')
            ->where('sales_project_id', $projectId)
            ->where('id', 55)
            ->where('status', 'lotte_sale_completion')
            ->update(['status' => 'processing', 'updated_at' => now()]);

        DB::table('applications')
            ->where('sales_project_id', $projectId)
            ->whereIn('id', [17, 20])
            ->where('status', 'lotte_rejected')
            ->update(['status' => 'rejected', 'updated_at' => now()]);
    }
};
