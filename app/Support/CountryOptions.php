<?php

namespace App\Support;

final class CountryOptions
{
    /**
     * @return list<array{code: string, name: string, currency: string}>
     */
    public static function forRegistration(): array
    {
        return collect(config('countries'))
            ->map(fn (array $data, string $code): array => [
                'code' => $code,
                'name' => $data['name'],
                'currency' => $data['currency'],
            ])
            ->values()
            ->all();
    }
}
