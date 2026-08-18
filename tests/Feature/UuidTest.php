<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_created_with_a_uuid(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $user->uuid);
    }

    public function test_uuid_is_unique_per_user(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->assertNotSame($a->uuid, $b->uuid);
    }

    public function test_me_endpoint_exposes_uuid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('uuid', $user->uuid)
            ->assertJsonMissing(['password']);
    }

    public function test_login_returns_uuid(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.uuid', $user->uuid)
            ->assertJsonMissingPath('user.password');
    }
}