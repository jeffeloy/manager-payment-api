<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Exceptions\PaymentRequestConflictException;
use App\Models\PaymentRequest;
use App\Models\User;

class PaymentRequestService
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
    }

    public function create(User $user, array $data): PaymentRequest
    {
        $currency = strtoupper($data['currency']);
        $snapshot = $this->exchangeRateService->getRateForCurrency($currency);
        $amount = (float) $data['amount'];

        return PaymentRequest::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'amount' => $amount,
            'currency' => $currency,
            'exchange_rate' => $snapshot->rate,
            'exchange_rate_source' => $snapshot->source,
            'exchange_rate_fetched_at' => $snapshot->fetchedAt,
            'amount_eur' => $this->exchangeRateService->convertToEur($amount, $snapshot->rate),
            'status' => PaymentRequestStatus::Pending,
        ]);
    }

    public function approve(PaymentRequest $paymentRequest, User $reviewer): PaymentRequest
    {
        $this->ensurePending($paymentRequest);

        $paymentRequest->update([
            'status' => PaymentRequestStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return $paymentRequest->fresh(['user', 'reviewer']);
    }

    public function reject(PaymentRequest $paymentRequest, User $reviewer, string $reason): PaymentRequest
    {
        $this->ensurePending($paymentRequest);

        $paymentRequest->update([
            'status' => PaymentRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $paymentRequest->fresh(['user', 'reviewer']);
    }

    public function expireStale(): int
    {
        return PaymentRequest::query()
            ->where('status', PaymentRequestStatus::Pending)
            ->where('created_at', '<=', now()->subHours(48))
            ->update(['status' => PaymentRequestStatus::Expired]);
    }

    private function ensurePending(PaymentRequest $paymentRequest): void
    {
        if (! $paymentRequest->status->isPending()) {
            throw new PaymentRequestConflictException(
                'Only pending payment requests can be approved or rejected.'
            );
        }
    }
}
