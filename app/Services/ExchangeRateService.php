<?php

namespace App\Services;

use App\Contracts\ExchangeRateProviderInterface;
use App\Data\ExchangeRateSnapshot;

class ExchangeRateService
{
    public function __construct(
        private readonly ExchangeRateProviderInterface $provider,
    ) {
    }

    public function getRateForCurrency(string $currency): ExchangeRateSnapshot
    {
        return $this->provider->getRateForCurrency($currency);
    }

    public function convertToEur(float $amount, float $exchangeRate): float
    {
        return round($amount / $exchangeRate, 2);
    }
}
