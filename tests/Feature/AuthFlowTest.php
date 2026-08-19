<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    private function skipIfMissing(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->markTestSkipped("$table not available");
        }
    }

    public function test_register_validation(): void
    {
        $response = $this->postJson('/api/auth/register', []);
        $response->assertStatus(422);
    }

    public function test_register_with_valid_data(): void
    {
        $this->skipIfMissing('users');
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@test.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_validation(): void
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(422);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $this->skipIfMissing('users');
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrong',
        ]);
        $response->assertStatus(422);
    }

    public function test_token_validation(): void
    {
        $response = $this->postJson('/api/auth/token', []);
        $response->assertStatus(422);
    }

    public function test_me_requires_auth(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_logout_requires_auth(): void
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    public function test_register_duplicate_email(): void
    {
        $this->skipIfMissing('users');
        $email = 'duplicate-' . uniqid() . '@test.com';
        $this->postJson('/api/auth/register', [
            'name' => 'First', 'email' => $email, 'password' => 'password123',
        ]);
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Second', 'email' => $email, 'password' => 'password123',
        ]);
        $response->assertStatus(422);
    }
}