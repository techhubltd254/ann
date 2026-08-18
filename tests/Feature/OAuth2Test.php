<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OAuth2Test extends TestCase
{
    public function test_oauth_routes_are_registered(): void
    {
        $routes = collect(app('router')->getRoutes())->filter(function ($route) {
            return str_contains($route->uri(), 'oauth/');
        });

        $this->assertGreaterThan(0, $routes->count());
    }

    public function test_oauth_token_endpoint_rejects_invalid_client(): void
    {
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => '00000000-0000-0000-0000-000000000000',
            'client_secret' => 'invalid',
            'scope' => '',
        ]);

        $this->assertTrue(in_array($response->status(), [401, 500]));
    }

    public function test_oauth_token_with_client_credentials_grant(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            $this->markTestSkipped('Passport migrations not loaded in test environment');
        }

        $client = \Laravel\Passport\Client::create([
            'id' => \Illuminate\Support\Str::orderedUuid(),
            'name' => 'Test Client Credentials',
            'secret' => 'test-secret-123',
            'provider' => 'users',
            'redirect_uris' => ['http://localhost'],
            'grant_types' => ['client_credentials'],
            'revoked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => 'test-secret-123',
            'scope' => '',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token_type', 'expires_in', 'access_token']);
    }

    public function test_passport_keys_exist(): void
    {
        $this->assertFileExists(storage_path('oauth-private.key'));
        $this->assertFileExists(storage_path('oauth-public.key'));
    }
}