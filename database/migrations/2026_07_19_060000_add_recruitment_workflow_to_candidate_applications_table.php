<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table): void {
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('interview_at')->nullable();
            $table->text('interview_note')->nullable();
            $table->string('interview_recommendation', 30)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();

            $table->index(['assigned_to_id', 'status']);
        });

        DB::table('candidate_applications')
            ->where('status', 'accepted')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        DB::table('candidate_applications')
            ->where('status', 'approved')
            ->update(['status' => 'accepted']);

        Schema::table('candidate_applications', function (Blueprint $table): void {
            $table->dropIndex(['assigned_to_id', 'status']);
            $table->dropConstrainedForeignId('assigned_to_id');
            $table->dropConstrainedForeignId('assigned_by_id');
            $table->dropConstrainedForeignId('approved_by_id');
            $table->dropColumn([
                'assigned_at',
                'interview_at',
                'interview_note',
                'interview_recommendation',
                'submitted_at',
                'approved_at',
                'approval_note',
            ]);
        });
    }
};
