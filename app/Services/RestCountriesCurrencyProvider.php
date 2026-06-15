<?php

namespace App\Services;

use App\Contracts\CountryCurrencyProviderInterface;
use App\Exceptions\CountryCurrencyUnavailableException;
use Illuminate\Support\Facades\Http;

class RestCountriesCurrencyProvider implements CountryCurrencyProviderInterface
{
    public function getCurrencyForCountry(string $country): string
    {
        $country = strtoupper($country);
        $baseUrl = rtrim((string) config('services.rest_countries.base_url'), '/');
        $apiKey = (string) config('services.rest_countries.api_key');

        $response = Http::timeout(10)
            ->withToken($apiKey)
            ->get("{$baseUrl}/code?q={$country}&response_fields=currencies&response_fields_omit=currencies.name, currencies.symbol");

        if (! $response->successful()) {
            throw new CountryCurrencyUnavailableException('Unable to fetch country currency from the provider.');
        }

        $currency = $this->firstCurrencyCode($response->json());

        if ($currency === null) {
            throw new CountryCurrencyUnavailableException("Currency not available for country [{$country}].");
        }

        return $currency;
    }

    private function firstCurrencyCode(mixed $payload): ?string
    {
        $code = data_get($payload, 'data.objects.0.currencies.0.code');

        return is_string($code) ? strtoupper($code) : null;
    }
}
