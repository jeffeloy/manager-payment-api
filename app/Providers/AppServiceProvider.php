<?php

namespace App\Providers;

use App\Contracts\ExchangeRateProviderInterface;
use App\Services\ExchangerateApiProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExchangeRateProviderInterface::class, ExchangerateApiProvider::class);
    }

    public function boot(): void
    {
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
