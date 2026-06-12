<?php

namespace App\Services;

use App\Contracts\ExchangeRateProviderInterface;
use App\Data\ExchangeRateSnapshot;
use App\Exceptions\ExchangeRateUnavailableException;
use Illuminate\Support\Facades\Http;

class ExchangerateApiProvider implements ExchangeRateProviderInterface
{
    public function getRateForCurrency(string $currency): ExchangeRateSnapshot
    {
        $currency = strtoupper($currency);
        $baseUrl = config('services.exchange_rate.base_url');
        $source = config('services.exchange_rate.source');

        $response = Http::timeout(10)->get("{$baseUrl}/latest/EUR");

        if (! $response->successful()) {
            throw new ExchangeRateUnavailableException('Unable to fetch exchange rates from the provider.');
        }

        $rates = $response->json('rates');

        if (! is_array($rates) || ! isset($rates[$currency])) {
            throw new ExchangeRateUnavailableException("Exchange rate not available for currency [{$currency}].");
        }

        $rate = (float) $rates[$currency];

        if ($rate <= 0) {
            throw new ExchangeRateUnavailableException("Invalid exchange rate returned for currency [{$currency}].");
        }

        return new ExchangeRateSnapshot(
            rate: $rate,
            source: $source,
            fetchedAt: now(),
        );
    }
}
