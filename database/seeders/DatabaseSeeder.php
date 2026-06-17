<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PassportKeyPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $this->installPassportClient();
        $users = [
            ['name' => 'Ana Silva', 'email' => 'ana.silva@manager.test', 'country' => 'BR', 'currency' => 'BRL', 'role' => UserRole::Employee],
            ['name' => 'John Smith', 'email' => 'john.smith@manager.test', 'country' => 'US', 'currency' => 'USD', 'role' => UserRole::Employee],
            ['name' => 'Emma Wilson', 'email' => 'emma.wilson@manager.test', 'country' => 'GB', 'currency' => 'GBP', 'role' => UserRole::Employee],
            ['name' => 'Yuki Tanaka', 'email' => 'yuki.tanaka@manager.test', 'country' => 'JP', 'currency' => 'JPY', 'role' => UserRole::Employee],
            ['name' => 'Hans Mueller', 'email' => 'hans.mueller@manager.test', 'country' => 'DE', 'currency' => 'EUR', 'role' => UserRole::Employee],
            ['name' => 'Sofia Rossi', 'email' => 'sofia.rossi@manager.test', 'country' => 'IT', 'currency' => 'EUR', 'role' => UserRole::Employee],
            ['name' => 'Finance Admin', 'email' => 'finance.admin@manager.test', 'country' => 'PT', 'currency' => 'EUR', 'role' => UserRole::Finance],
            ['name' => 'Finance Reviewer', 'email' => 'finance.reviewer@manager.test', 'country' => 'PT', 'currency' => 'EUR', 'role' => UserRole::Finance],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'country' => $userData['country'],
                    'currency' => $userData['currency'],
                    'role' => $userData['role'],
                ]
            );
        }

        $this->call(PaymentRequestSeeder::class);
    }

    private function installPassportClient(): void
    {
        $hasKeyFiles = file_exists(storage_path('oauth-private.key'));
        $hasKeyEnv = config('passport.private_key') !== null && config('passport.private_key') !== '';

        if (! $hasKeyFiles && ! $hasKeyEnv) {
            $this->command?->call('passport:keys', ['--force' => true]);
        }

        PassportKeyPermissions::fix();

        $clients = app(ClientRepository::class);

        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Personal Access Client', 'users');
        }
    }
}
