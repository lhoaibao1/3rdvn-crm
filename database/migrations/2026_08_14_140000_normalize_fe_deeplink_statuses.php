<?php

use App\Enums\FeDeeplinkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $projectId = DB::table('sales_projects')->where('slug', 'fe-deeplink')->value('id');

        if (! $projectId) {
            return;
        }

        DB::table('applications')->where('sales_project_id', $projectId)->where('status', 'approved')->update(['status' => FeDeeplinkStatus::PL_DISBURSED->value]);
        DB::table('applications')->where('sales_project_id', $projectId)->whereIn('status', ['rejected', 'ineligible'])->update(['status' => FeDeeplinkStatus::HARD_REJECT->value]);
    }

    public function down(): void
    {
        $projectId = DB::table('sales_projects')->where('slug', 'fe-deeplink')->value('id');

        if (! $projectId) {
            return;
        }

        DB::table('applications')->where('sales_project_id', $projectId)->where('status', FeDeeplinkStatus::PL_DISBURSED->value)->update(['status' => 'approved']);
        DB::table('applications')->where('sales_project_id', $projectId)->where('status', FeDeeplinkStatus::HARD_REJECT->value)->update(['status' => 'rejected']);
    }
};
