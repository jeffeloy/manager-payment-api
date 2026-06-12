<?php

namespace Database\Factories;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    protected $model = PaymentRequest::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 5000);
        $exchangeRate = fake()->randomFloat(4, 1, 10);

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'amount' => $amount,
            'currency' => 'BRL',
            'exchange_rate' => $exchangeRate,
            'exchange_rate_source' => 'https://api.exchangerate-api.com',
            'exchange_rate_fetched_at' => now(),
            'amount_eur' => round($amount / $exchangeRate, 4),
            'status' => PaymentRequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRequestStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRequestStatus::Rejected,
            'rejection_reason' => 'Insufficient documentation.',
            'reviewed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRequestStatus::Expired,
        ]);
    }
}
