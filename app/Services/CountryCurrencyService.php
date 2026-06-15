<?php

namespace App\Services;

use App\Contracts\CountryCurrencyProviderInterface;
use Illuminate\Support\Facades\Cache;

class CountryCurrencyService
{
    private const CACHE_PREFIX = 'country_currency:';

    public function __construct(
        private readonly CountryCurrencyProviderInterface $provider,
    ) {
    }

    public function getCurrencyForCountry(string $country): string
    {
        return Cache::remember(self::CACHE_PREFIX . strtoupper($country), now()->addDay(), function () use ($country) {
            return $this->provider->getCurrencyForCountry($country);
        });
    }
}
