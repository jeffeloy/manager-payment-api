<?php

namespace Tests;

use Laravel\Passport\ClientRepository;
use RuntimeException;

trait InstallsPassport
{
    protected function installPassport(): void
    {
        $this->artisan('passport:keys', ['--force' => true]);

        $clients = app(ClientRepository::class);

        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Test Personal Access Client', 'users');
        }
    }
}
