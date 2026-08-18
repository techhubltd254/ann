<?php

namespace Tests\Feature;

use App\Models\County;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CountyRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_counties_index_returns_200(): void
    {
        County::create([
            'name' => 'Mombasa',
            'capital' => 'Mombasa City',
            'code' => 'KE-001',
            'former_province' => 'Coast',
            'economic_zone' => 'Coastal Strip',
            'population_2024' => 1260000,
            'area_km2' => 229.7,
            'is_active' => true,
        ]);

        $response = $this->get('/counties');

        $response->assertStatus(200);
        $response->assertSee('Mombasa');
    }

    public function test_counties_show_returns_200_for_known_county(): void
    {
        $county = County::create([
            'name' => 'Nairobi City',
            'capital' => 'Nairobi',
            'code' => 'KE-030',
            'former_province' => 'Nairobi',
            'economic_zone' => 'Metropolitan',
            'population_2024' => 4500000,
            'area_km2' => 696,
            'is_active' => true,
        ]);

        $response = $this->get("/counties/{$county->slug}");

        $response->assertStatus(200);
        $response->assertSee('Nairobi City');
    }

    public function test_unknown_county_returns_404(): void
    {
        $this->get('/counties/does-not-exist')->assertStatus(404);
    }
}
