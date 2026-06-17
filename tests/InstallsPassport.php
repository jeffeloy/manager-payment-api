<?php

namespace Tests;

use App\Support\PassportKeyPermissions;
use Laravel\Passport\ClientRepository;
use RuntimeException;

trait InstallsPassport
{
    protected function installPassport(): void
    {
        $this->artisan('passport:keys', ['--force' => true]);
        PassportKeyPermissions::fix();

        $clients = app(ClientRepository::class);

        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Test Personal Access Client', 'users');
        }
    }
}
