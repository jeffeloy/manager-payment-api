<?php

namespace Tests\Feature\Web;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentRequestWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_employee_can_view_dashboard(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PaymentRequest/Index')
                ->has('paymentRequests')
                ->has('stats'));
    }

    public function test_employee_can_create_payment_request_via_web(): void
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

        $this->actingAs($employee)
            ->post(route('payment-requests.store'), [
                'title' => 'Office supplies',
                'amount' => 595,
                'currency' => 'BRL',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('payment_requests', [
            'user_id' => $employee->id,
            'title' => 'Office supplies',
            'currency' => 'BRL',
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
