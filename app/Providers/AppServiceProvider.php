<?php

namespace App\Providers;

use App\Contracts\ExchangeRateProviderInterface;
use App\Models\PaymentRequest;
use App\Policies\PaymentRequestPolicy;
use App\Services\ExchangerateApiProvider;
use App\Support\PassportKeyPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ExchangeRateProviderInterface::class,
            ExchangerateApiProvider::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(PaymentRequest::class, PaymentRequestPolicy::class);

        PassportKeyPermissions::fix();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Vite::prefetch(concurrency: 3);
    }
}
