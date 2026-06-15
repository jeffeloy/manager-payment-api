<?php

namespace App\Providers;

use App\Contracts\ExchangeRateProviderInterface;
use App\Contracts\CountryCurrencyProviderInterface;
use App\Services\ExchangerateApiProvider;
use App\Services\RestCountriesCurrencyProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ExchangeRateProviderInterface::class,
            ExchangerateApiProvider::class,
        );

        $this->app->bind(
            CountryCurrencyProviderInterface::class,
            RestCountriesCurrencyProvider::class,
        );
    }

    public function boot(): void
    {
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
