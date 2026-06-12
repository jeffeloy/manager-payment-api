<?php

namespace App\Data;

use Carbon\CarbonInterface;

readonly class ExchangeRateSnapshot
{
    public function __construct(
        public float $rate,
        public string $source,
        public CarbonInterface $fetchedAt,
    ) {
    }
}
