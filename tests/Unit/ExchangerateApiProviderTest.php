<?php

namespace Tests\Unit;

use App\Data\ExchangeRateSnapshot;
use App\Exceptions\ExchangeRateUnavailableException;
use App\Services\ExchangerateApiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangerateApiProviderTest extends TestCase
{
    public function test_it_fetches_and_parses_exchange_rate_for_currency(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => [
                    'EUR' => 1,
                    'BRL' => 5.95,
                ],
            ], 200),
        ]);

        $provider = new ExchangerateApiProvider();
        $snapshot = $provider->getRateForCurrency('BRL');

        $this->assertInstanceOf(ExchangeRateSnapshot::class, $snapshot);
        $this->assertSame(5.95, $snapshot->rate);
        $this->assertSame('https://api.exchangerate-api.com', $snapshot->source);
    }

    public function test_it_throws_when_provider_is_unavailable(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([], 500),
        ]);

        $this->expectException(ExchangeRateUnavailableException::class);

        (new ExchangerateApiProvider())->getRateForCurrency('BRL');
    }

    public function test_it_throws_when_currency_is_missing(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => ['EUR' => 1],
            ], 200),
        ]);

        $this->expectException(ExchangeRateUnavailableException::class);

        (new ExchangerateApiProvider())->getRateForCurrency('XYZ');
    }
}
