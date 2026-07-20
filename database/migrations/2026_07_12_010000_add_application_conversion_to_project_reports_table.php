<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_reports', function (Blueprint $table): void {
            $table->foreignId('application_id')
                ->nullable()
                ->unique()
                ->after('sales_project_id')
                ->constrained('applications')
                ->nullOnDelete();
            $table->string('origin', 30)->default('manual')->after('application_id');
            $table->foreignId('converted_by_id')->nullable()->after('status_updated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('converted_by_id');
            $table->index(['origin', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('project_reports', function (Blueprint $table): void {
            $table->dropIndex(['origin', 'created_at']);
            $table->dropConstrainedForeignId('converted_by_id');
            $table->dropColumn(['converted_at', 'origin']);
            $table->dropConstrainedForeignId('application_id');
        });
    }
};
