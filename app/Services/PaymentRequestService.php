<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Exceptions\PaymentRequestConflictException;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PaymentRequestService
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
    }

    public function queryForUser(User $user, ?string $status = null): Builder
    {
        $query = PaymentRequest::query()
            ->with(['user', 'reviewer'])
            ->latest();

        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function listForUser(User $user, ?string $status = null): Collection
    {
        return $this->queryForUser($user, $status)->get();
    }

    /**
     * @return array{pending: int, approved: int, rejected: int, expired: int}
     */
    public function statsForUser(User $user): array
    {
        $stats = $this->queryForUser($user)
            ->clone()
            ->reorder()
            ->selectRaw("
            count(case when status = 'pending' then 1 end) as pending,
            count(case when status = 'approved' then 1 end) as approved,
            count(case when status = 'rejected' then 1 end) as rejected,
            count(case when status = 'expired' then 1 end) as expired
        ")->first();

        return [
            'pending' => (int) $stats->pending,
            'approved' => (int) $stats->approved,
            'rejected' => (int) $stats->rejected,
            'expired' => (int) $stats->expired,
        ];
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
