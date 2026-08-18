<?php

namespace Tests\Unit;

use App\Models\County;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CountyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_from_name(): void
    {
        $county = County::create([
            'name' => 'Tana River',
            'capital' => 'Hola',
            'code' => 'KE-004',
            'former_province' => 'Coast',
            'economic_zone' => 'Riverine',
            'population_2024' => 315943,
            'area_km2' => 37113,
            'is_active' => true,
        ]);

        $this->assertSame('tana-river', $county->slug);
    }
}
