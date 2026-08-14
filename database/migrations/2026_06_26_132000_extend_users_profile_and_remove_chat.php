<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'date_of_birth' => fn () => $table->date('date_of_birth')->nullable()->after('phone'),
                'gender' => fn () => $table->string('gender', 20)->nullable()->after('date_of_birth'),
                'identity_number' => fn () => $table->string('identity_number', 50)->nullable()->after('gender'),
                'identity_issued_date' => fn () => $table->date('identity_issued_date')->nullable()->after('identity_number'),
                'identity_issued_place' => fn () => $table->string('identity_issued_place')->nullable()->after('identity_issued_date'),
                'department' => fn () => $table->string('department')->nullable()->after('identity_issued_place'),
                'position' => fn () => $table->string('position')->nullable()->after('department'),
                'employment_status' => fn () => $table->string('employment_status', 50)->nullable()->after('position'),
                'hire_date' => fn () => $table->date('hire_date')->nullable()->after('employment_status'),
                'address_line' => fn () => $table->string('address_line')->nullable()->after('hire_date'),
                'province_name' => fn () => $table->string('province_name')->nullable()->after('address_line'),
                'district_name' => fn () => $table->string('district_name')->nullable()->after('province_name'),
                'ward_name' => fn () => $table->string('ward_name')->nullable()->after('district_name'),
                'bank_code' => fn () => $table->string('bank_code', 50)->nullable()->after('ward_name'),
                'bank_name' => fn () => $table->string('bank_name')->nullable()->after('bank_code'),
                'bank_account_number' => fn () => $table->string('bank_account_number', 80)->nullable()->after('bank_name'),
                'bank_account_name' => fn () => $table->string('bank_account_name')->nullable()->after('bank_account_number'),
                'bank_branch' => fn () => $table->string('bank_branch')->nullable()->after('bank_account_name'),
                'tax_code' => fn () => $table->string('tax_code', 80)->nullable()->after('bank_branch'),
                'social_insurance_number' => fn () => $table->string('social_insurance_number', 80)->nullable()->after('tax_code'),
                'emergency_contact_name' => fn () => $table->string('emergency_contact_name')->nullable()->after('social_insurance_number'),
                'emergency_contact_phone' => fn () => $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name'),
            ] as $column => $definition) {
                if (! Schema::hasColumn('users', $column)) {
                    $definition();
                }
            }
        });

        DB::table('users')->whereNull('employment_status')->update([
            'employment_status' => 'active',
        ]);

        if (Schema::hasTable('crm_modules')) {
            DB::table('crm_modules')->where('slug', 'chat')->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', ['chat.view', 'chat.send'])->delete();
        }

        Schema::dropIfExists('chat_conversation_user');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'emergency_contact_phone', 'emergency_contact_name', 'social_insurance_number', 'tax_code',
                'bank_branch', 'bank_account_name', 'bank_account_number', 'bank_name', 'bank_code',
                'ward_name', 'district_name', 'province_name', 'address_line', 'hire_date', 'employment_status',
                'position', 'department', 'identity_issued_place', 'identity_issued_date', 'identity_number',
                'gender', 'date_of_birth',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
