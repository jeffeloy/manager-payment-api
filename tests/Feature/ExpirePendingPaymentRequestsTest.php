<?php

namespace Tests\Feature;

use App\Enums\PaymentRequestStatus;
use App\Enums\UserRole;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePendingPaymentRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_only_pending_requests_older_than_48_hours(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $recentPending = PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);
        $recentPending->forceFill(['created_at' => now()->subHours(24)])->save();

        $stalePending = PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);
        $stalePending->forceFill(['created_at' => now()->subHours(49)])->save();

        $approved = PaymentRequest::factory()->approved()->create([
            'user_id' => $employee->id,
        ]);
        $approved->forceFill(['created_at' => now()->subHours(72)])->save();

        $this->artisan('payment-requests:expire')
            ->expectsOutput('Expired 1 pending payment request(s).')
            ->assertSuccessful();

        $this->assertSame(PaymentRequestStatus::Pending, $recentPending->fresh()->status);
        $this->assertSame(PaymentRequestStatus::Expired, $stalePending->fresh()->status);
        $this->assertSame(PaymentRequestStatus::Approved, $approved->fresh()->status);
    }
}
