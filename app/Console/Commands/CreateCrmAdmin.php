<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateCrmAdmin extends Command
{
    protected $signature = 'crm:create-admin {--name=} {--email=} {--password=} {--employee_code=}';

    protected $description = 'Create or update the first 3RDVN CRM admin user.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $employeeCode = $this->option('employee_code') ?: $this->ask('Employee code optional', null);

        Role::findOrCreate('Admin');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'employee_code' => $employeeCode,
            ],
        );

        $user->syncRoles(['Admin']);
        $this->info("Admin ready: {$user->email}");

        return self::SUCCESS;
    }
}
