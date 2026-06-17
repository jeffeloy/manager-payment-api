<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CurrencyMatchesCountry implements ValidationRule
{
    public function __construct(
        private readonly ?string $country,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->country === null) {
            return;
        }

        $country = strtoupper($this->country);
        $expectedCurrency = config("countries.{$country}.currency");

        if ($expectedCurrency === null) {
            $fail('The selected country is not supported.');

            return;
        }

        if (strtoupper($value) !== $expectedCurrency) {
            $fail("The currency must match the selected country [{$expectedCurrency}].");
        }
    }
}
