<?php

namespace App\Console\Commands;

use App\Services\PaymentRequestService;
use Illuminate\Console\Command;

class ExpirePendingPaymentRequests extends Command
{
    protected $signature = 'payment-requests:expire';

    protected $description = 'Expire pending payment requests older than 48 hours';

    public function handle(PaymentRequestService $paymentRequestService): int
    {
        $expiredCount = $paymentRequestService->expireStale();

        $this->info("Expired {$expiredCount} pending payment request(s).");

        return self::SUCCESS;
    }
}
