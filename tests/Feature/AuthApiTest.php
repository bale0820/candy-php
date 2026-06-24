<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_session_user(): void
    {
        $response = $this->fromFrontend()->postJson('/api/auth/register', [
            'name' => 'candy-dev',
            'email' => 'candy@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'candy-dev')
            ->assertJsonPath('data.email', 'candy@example.com');

        $this->assertAuthenticated();
    }

    public function test_user_can_login_and_logout_with_session(): void
    {
        User::factory()->create([
            'email' => 'dev@example.com',
            'password' => 'password123',
        ]);

        $this->fromFrontend()->postJson('/api/auth/login', [
            'email' => 'dev@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'dev@example.com');

        $this->assertAuthenticated();

        $this->fromFrontend()->postJson('/api/auth/logout')->assertOk();

        $this->fromFrontend()->getJson('/api/auth/me')->assertUnauthorized();
    }

    private function fromFrontend(): static
    {
        return $this
            ->withHeader('Origin', 'http://localhost:5173')
            ->withHeader('Referer', 'http://localhost:5173/');
    }
}
