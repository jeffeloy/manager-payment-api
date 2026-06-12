<?php

namespace App\Contracts;

use App\Data\ExchangeRateSnapshot;

interface ExchangeRateProviderInterface
{
    public function getRateForCurrency(string $currency): ExchangeRateSnapshot;
}
