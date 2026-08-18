<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_login_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(422);
    }

    public function test_login_with_invalid_credentials(): void
    {
        // Skip if the users table doesn't exist (test env without migrations)
        if (! Schema::hasTable('users')) {
            $this->markTestSkipped('users table not available in test environment');
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(422);
    }

    public function test_register_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/register', []);
        $response->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    public function test_token_endpoint_returns_validation_error(): void
    {
        $response = $this->postJson('/api/auth/token', []);
        $response->assertStatus(422);
    }
}