<?php

namespace Tests\Feature;

use App\Models\County;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_returns_200(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_api_counties_returns_json_list(): void
    {
        County::create([
            'name' => 'Nairobi City',
            'capital' => 'Nairobi',
            'code' => 'KE-030',
            'former_province' => 'Nairobi',
            'economic_zone' => 'Metropolitan',
            'population_2024' => 4500000,
            'area_km2' => 696,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/counties');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Nairobi City');
    }
}
