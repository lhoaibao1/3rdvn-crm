<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('sales_channels')) {
            Schema::create('sales_channels', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('branch_name')->nullable();
                $table->string('branch_code')->nullable();
                $table->string('channel_name')->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        DB::table('sales_channels')->updateOrInsert(
            ['company_name' => '3RDVN', 'branch_code' => 'RDVN', 'channel_name' => 'F1'],
            [
                'branch_name' => '3RDVN - HCMC',
                'note' => 'Tạo từ file phân quyền user. Có thể chỉnh sau.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'uid' => fn () => $table->string('uid', 50)->nullable()->unique()->after('id'),
                'document_type' => fn () => $table->string('document_type', 50)->nullable()->after('phone'),
                'office' => fn () => $table->string('office')->nullable()->after('hire_date'),
                'contract_type' => fn () => $table->string('contract_type')->nullable()->after('office'),
                'sales_projects' => fn () => $table->json('sales_projects')->nullable()->after('contract_type'),
                'sales_codes' => fn () => $table->json('sales_codes')->nullable()->after('sales_projects'),
                'company_name' => fn () => $table->string('company_name')->nullable()->after('sales_codes'),
                'branch_name' => fn () => $table->string('branch_name')->nullable()->after('company_name'),
                'branch_code' => fn () => $table->string('branch_code')->nullable()->after('branch_name'),
                'sales_channel' => fn () => $table->string('sales_channel')->nullable()->after('branch_code'),
                'team_leader_id' => fn () => $table->foreignId('team_leader_id')->nullable()->after('sales_channel')->constrained('users')->nullOnDelete(),
                'am_id' => fn () => $table->foreignId('am_id')->nullable()->after('team_leader_id')->constrained('users')->nullOnDelete(),
                'zd_id' => fn () => $table->foreignId('zd_id')->nullable()->after('am_id')->constrained('users')->nullOnDelete(),
                'created_by_id' => fn () => $table->foreignId('created_by_id')->nullable()->after('zd_id')->constrained('users')->nullOnDelete(),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('users', $column)) {
                    $definition();
                }
            }
        });

        DB::table('users')
            ->whereNull('uid')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($user, int $index): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'uid' => 'UID'.now()->format('ym').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['created_by_id', 'zd_id', 'am_id', 'team_leader_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'sales_channel', 'branch_code', 'branch_name', 'company_name', 'sales_codes', 'sales_projects',
                'contract_type', 'office', 'document_type', 'uid',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('sales_channels');
    }
};
