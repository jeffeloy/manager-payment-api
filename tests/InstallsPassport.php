<?php

namespace Tests;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

trait InstallsPassport
{
    protected function installPassport(): void
    {
        $this->artisan('passport:keys', ['--force' => true]);

        if (Client::query()->where('personal_access_client', true)->doesntExist()) {
            app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Access Client');
        }
    }
}
