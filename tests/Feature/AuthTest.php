<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\InstallsPassport;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InstallsPassport;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installPassport();
    }

    public function test_user_can_register_and_receive_access_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Employee',
            'email' => 'new.employee@manager.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'BR',
            'currency' => 'BRL',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'country', 'currency'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new.employee@manager.test',
            'role' => 'employee',
            'country' => 'BR',
            'currency' => 'BRL',
        ]);
    }

    public function test_register_rejects_currency_that_does_not_match_country(): void
    {
        $this->postJson('/api/register', [
            'name' => 'New Employee',
            'email' => 'new.employee@manager.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'BR',
            'currency' => 'USD',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'login.user@manager.test',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'login.user@manager.test',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('token_type', 'Bearer');

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        User::factory()->create([
            'email' => 'invalid.login@manager.test',
            'password' => 'password123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'invalid.login@manager.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile.user@manager.test',
        ]);

        Passport::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Profile User')
            ->assertJsonPath('data.email', 'profile.user@manager.test')
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'role', 'country', 'currency', 'created_at'],
            ]);
    }

    public function test_protected_routes_return_401_without_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->getJson('/api/payment-requests')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
