<?php

namespace Tests\Unit;

use App\Data\ExchangeRateSnapshot;
use App\Enums\PaymentRequestStatus;
use App\Enums\UserRole;
use App\Exceptions\PaymentRequestConflictException;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\PaymentRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\InstallsPassport;
use Tests\TestCase;

class PaymentRequestServiceTest extends TestCase
{
    use InstallsPassport;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installPassport();
    }

    public function test_it_creates_payment_request_with_immutable_exchange_rate(): void
    {
        $exchangeRateService = Mockery::mock(ExchangeRateService::class);
        $exchangeRateService->shouldReceive('getRateForCurrency')
            ->once()
            ->with('BRL')
            ->andReturn(new ExchangeRateSnapshot(5.95, 'https://api.exchangerate-api.com', now()));
        $exchangeRateService->shouldReceive('convertToEur')
            ->once()
            ->with(595.0, 5.95)
            ->andReturn(100.0);

        $service = new PaymentRequestService($exchangeRateService);

        $user = User::factory()->create([
            'role' => UserRole::Employee,
            'country' => 'BR',
            'currency' => 'BRL',
        ]);

        $paymentRequest = $service->create($user, [
            'title' => 'Office supplies',
            'amount' => 595,
            'currency' => 'BRL',
        ]);

        $this->assertSame(PaymentRequestStatus::Pending, $paymentRequest->status);
        $this->assertSame('595.0000', $paymentRequest->amount);
        $this->assertSame('5.95000000', $paymentRequest->exchange_rate);
        $this->assertSame('100.0000', $paymentRequest->amount_eur);
    }

    public function test_it_approves_pending_payment_request(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => ['EUR' => 1],
            ], 200),
        ]);

        $service = new PaymentRequestService(app(ExchangeRateService::class));

        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $finance = User::factory()->finance()->create();

        $paymentRequest = $service->create($employee, [
            'title' => 'Travel',
            'amount' => 100,
            'currency' => 'EUR',
        ]);

        $approved = $service->approve($paymentRequest, $finance);

        $this->assertSame(PaymentRequestStatus::Approved, $approved->status);
        $this->assertSame($finance->id, $approved->reviewed_by);
    }

    public function test_it_rejects_pending_payment_request(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => ['EUR' => 1],
            ], 200),
        ]);

        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $finance = User::factory()->finance()->create();

        $paymentRequest = $service->create($employee, [
            'title' => 'Travel',
            'amount' => 100,
            'currency' => 'EUR',
        ]);

        $rejected = $service->reject($paymentRequest, $finance, 'Missing receipt');

        $this->assertSame(PaymentRequestStatus::Rejected, $rejected->status);
        $this->assertSame('Missing receipt', $rejected->rejection_reason);
    }

    public function test_it_expires_stale_pending_requests(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => ['EUR' => 1, 'BRL' => 5.95],
            ], 200),
        ]);

        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $recent = $service->create($employee, [
            'title' => 'Recent',
            'amount' => 100,
            'currency' => 'BRL',
        ]);

        $stale = $service->create($employee, [
            'title' => 'Stale',
            'amount' => 200,
            'currency' => 'BRL',
        ]);

        $stale->forceFill(['created_at' => now()->subHours(49)])->save();

        $expiredCount = $service->expireStale();

        $this->assertSame(1, $expiredCount);
        $this->assertSame(PaymentRequestStatus::Pending, $recent->fresh()->status);
        $this->assertSame(PaymentRequestStatus::Expired, $stale->fresh()->status);
    }

    public function test_it_prevents_approving_non_pending_request(): void
    {
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/EUR' => Http::response([
                'rates' => ['EUR' => 1],
            ], 200),
        ]);

        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $finance = User::factory()->finance()->create();

        $paymentRequest = $service->create($employee, [
            'title' => 'Travel',
            'amount' => 100,
            'currency' => 'EUR',
        ]);

        $service->approve($paymentRequest, $finance);

        $this->expectException(PaymentRequestConflictException::class);

        $service->approve($paymentRequest->fresh(), $finance);
    }

    public function test_query_for_user_scopes_to_own_requests_for_employee(): void
    {
        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee]);

        PaymentRequest::factory()->create(['user_id' => $employee->id, 'title' => 'Mine']);
        PaymentRequest::factory()->create(['user_id' => $otherEmployee->id, 'title' => 'Other']);

        $results = $service->listForUser($employee);

        $this->assertCount(1, $results);
        $this->assertSame('Mine', $results->first()->title);
    }

    public function test_query_for_user_returns_all_for_finance(): void
    {
        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $finance = User::factory()->finance()->create();
        $employeeA = User::factory()->create(['role' => UserRole::Employee]);
        $employeeB = User::factory()->create(['role' => UserRole::Employee]);

        PaymentRequest::factory()->create(['user_id' => $employeeA->id]);
        PaymentRequest::factory()->create(['user_id' => $employeeB->id]);

        $results = $service->listForUser($finance);

        $this->assertCount(2, $results);
    }

    public function test_query_for_user_filters_by_status(): void
    {
        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);

        PaymentRequest::factory()->approved()->create([
            'user_id' => $employee->id,
        ]);

        $results = $service->listForUser($employee, PaymentRequestStatus::Pending->value);

        $this->assertCount(1, $results);
        $this->assertSame(PaymentRequestStatus::Pending, $results->first()->status);
    }

    public function test_stats_for_user_counts_by_status(): void
    {
        $service = new PaymentRequestService(app(ExchangeRateService::class));
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee]);
        $finance = User::factory()->finance()->create();

        PaymentRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => PaymentRequestStatus::Pending,
        ]);

        PaymentRequest::factory()->approved()->create([
            'user_id' => $employee->id,
        ]);

        PaymentRequest::factory()->rejected()->create([
            'user_id' => $otherEmployee->id,
        ]);

        $employeeStats = $service->statsForUser($employee);
        $financeStats = $service->statsForUser($finance);

        $this->assertSame(1, $employeeStats['pending']);
        $this->assertSame(1, $employeeStats['approved']);
        $this->assertSame(0, $employeeStats['rejected']);

        $this->assertSame(1, $financeStats['pending']);
        $this->assertSame(1, $financeStats['approved']);
        $this->assertSame(1, $financeStats['rejected']);
    }
}
