<?php

namespace App\Contracts;

interface CountryCurrencyProviderInterface
{
    public function getCurrencyForCountry(string $country): string;
}
