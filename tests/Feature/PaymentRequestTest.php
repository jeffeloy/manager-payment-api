<?php

namespace Tests\Feature;

use App\Enums\PaymentRequestStatus;
use App\Enums\UserRole;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\InstallsPassport;
use Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    use InstallsPassport;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installPassport();
    }

    public function test_employee_can_create_payment_request_with_exchange_rate(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => ['BRL' => 5.95],
            ], 200),
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'currency' => 'BRL',
        ]);

        Passport::actingAs($employee);

        $response = $this->postJson('/api/payment-requests', [
            'title' => 'Office supplies',
            'amount' => 595,
            'currency' => 'BRL',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount_eur', 100)
            ->assertJsonPath('data.exchange_rate', 5.95)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_employee_only_sees_own_payment_requests(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee]);

        PaymentRequest::factory()->create(['user_id' => $employee->id, 'title' => 'Mine']);
        PaymentRequest::factory()->create(['user_id' => $otherEmployee->id, 'title' => 'Other']);

        Passport::actingAs($employee);

        $response = $this->getJson('/api/payment-requests');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Mine', $response->json('data.0.title'));
    }

    public function test_finance_user_can_list_all_payment_requests_with_status_filter(): void
    {
        $finance = User::factory()->finance()->create();
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);

        PaymentRequest::factory()->approved()->create([
            'user_id' => $employee->id,
        ]);

        Passport::actingAs($finance);

        $response = $this->getJson('/api/payment-requests?status=pending');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('pending', $response->json('data.0.status'));
    }

    public function test_finance_user_can_approve_and_reject_pending_requests(): void
    {
        $finance = User::factory()->finance()->create();
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $pending = PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);

        $toReject = PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);

        Passport::actingAs($finance);

        $this->patchJson("/api/payment-requests/{$pending->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->patchJson("/api/payment-requests/{$toReject->id}/reject", [
            'rejection_reason' => 'Missing receipt',
        ])->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Missing receipt');
    }

    public function test_employee_cannot_approve_payment_requests(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $paymentRequest = PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);

        Passport::actingAs($employee);

        $this->patchJson("/api/payment-requests/{$paymentRequest->id}/approve")
            ->assertForbidden();
    }

    public function test_create_payment_request_returns_503_when_exchange_rate_is_unavailable(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([], 500),
        ]);

        $employee = User::factory()->create(['role' => UserRole::Employee]);
        Passport::actingAs($employee);

        $this->postJson('/api/payment-requests', [
            'title' => 'Office supplies',
            'amount' => 100,
            'currency' => 'BRL',
        ])->assertStatus(503);
    }
}
