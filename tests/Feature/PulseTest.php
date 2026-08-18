<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PulseTest extends TestCase
{
    use RefreshDatabase;

    public function test_pulse_requires_authentication(): void
    {
        $this->get('/pulse')->assertStatus(403);
    }

    public function test_pulse_denies_non_admin(): void
    {
        $user = User::create([
            'name' => 'Regular',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)->get('/pulse')->assertStatus(403);
    }

    public function test_pulse_allows_superadmin(): void
    {
        $user = User::create([
            'name' => 'Super',
            'email' => 'admin@kicc.go.ke',
            'password' => bcrypt('password'),
            'account_type' => 'superadmin',
        ]);

        $this->actingAs($user)->get('/pulse')->assertStatus(200);
    }
}
