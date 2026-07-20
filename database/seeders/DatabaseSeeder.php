<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CrmModuleSeeder::class,
        ]);

        if (class_exists(\App\Models\UiSetting::class)) {
            \App\Models\UiSetting::query()->firstOrCreate(['id' => 1], \App\Models\UiSetting::defaults());
        }
    }
}
