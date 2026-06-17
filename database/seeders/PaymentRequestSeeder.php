<?php

namespace Database\Seeders;

use App\Enums\PaymentRequestStatus;
use App\Enums\UserRole;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentRequestSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::query()->where('role', UserRole::Employee)->get();
        $financeAdmin = User::query()->where('email', 'finance.admin@manager.test')->first();

        if ($employees->isEmpty()) {
            return;
        }

        $ana = $employees->firstWhere('email', 'ana.silva@manager.test') ?? $employees->first();

        PaymentRequest::query()->updateOrCreate(
            ['user_id' => $ana->id, 'title' => 'Office supplies reimbursement'],
            [
                'amount' => 595.00,
                'currency' => 'BRL',
                'exchange_rate' => 5.9500,
                'exchange_rate_source' => 'https://api.exchangerate-api.com',
                'exchange_rate_fetched_at' => now()->subDay(),
                'amount_eur' => 100.0000,
                'status' => PaymentRequestStatus::Pending,
            ],
        );

        PaymentRequest::query()->updateOrCreate(
            ['user_id' => $ana->id, 'title' => 'Travel advance'],
            [
                'amount' => 1200.00,
                'currency' => 'BRL',
                'exchange_rate' => 6.0000,
                'exchange_rate_source' => 'https://api.exchangerate-api.com',
                'exchange_rate_fetched_at' => now()->subDays(3),
                'amount_eur' => 200.0000,
                'status' => PaymentRequestStatus::Approved,
                'reviewed_by' => $financeAdmin?->id,
                'reviewed_at' => now()->subDays(2),
            ],
        );

        $john = $employees->firstWhere('email', 'john.smith@manager.test');

        if ($john !== null) {
            PaymentRequest::query()->updateOrCreate(
                ['user_id' => $john->id, 'title' => 'Client dinner'],
                [
                    'amount' => 250.00,
                    'currency' => 'USD',
                    'exchange_rate' => 1.1600,
                    'exchange_rate_source' => 'https://api.exchangerate-api.com',
                    'exchange_rate_fetched_at' => now()->subDays(4),
                    'amount_eur' => 215.5172,
                    'status' => PaymentRequestStatus::Rejected,
                    'reviewed_by' => $financeAdmin?->id,
                    'reviewed_at' => now()->subDays(3),
                    'rejection_reason' => 'Missing receipt attachment.',
                ],
            );
        }
    }
}
