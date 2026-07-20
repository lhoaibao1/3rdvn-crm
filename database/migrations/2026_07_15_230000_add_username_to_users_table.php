<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 63)->nullable();
        });

        $normalize = static function (?string $value): string {
            $value = Str::lower(Str::ascii(trim((string) $value)));
            $value = preg_replace('/[^a-z0-9._-]+/', '.', $value) ?? '';

            return Str::limit(trim($value, '._-'), 63, '');
        };

        $used = [];

        DB::table('users')
            ->select(['id', 'email', 'uid'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$used, $normalize): void {
                foreach ($users as $user) {
                    $emailLocalPart = Str::before((string) $user->email, '@');
                    $base = $normalize($emailLocalPart ?: $user->uid ?: 'user-'.$user->id);
                    $base = $base ?: 'user-'.$user->id;
                    $candidate = $base;
                    $suffix = 1;

                    while (isset($used[$candidate])) {
                        $suffix++;
                        $tail = '-'.$suffix;
                        $candidate = Str::limit($base, 63 - strlen($tail), '').$tail;
                    }

                    $used[$candidate] = true;

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $candidate]);
                }
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
