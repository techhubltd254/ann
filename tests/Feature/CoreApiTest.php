<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreApiTest extends TestCase
{
    private function skipIfMissing(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->markTestSkipped("$table table not available");
        }
    }

    public function test_counties_index(): void
    {
        $this->skipIfMissing('counties');
        $response = $this->getJson('/api/counties');
        $response->assertOk();
    }

    public function test_exhibitions_index(): void
    {
        $this->skipIfMissing('exhibitions');
        $response = $this->getJson('/api/exhibitions');
        $response->assertOk();
    }

    public function test_venues_index(): void
    {
        $this->skipIfMissing('venues');
        $response = $this->getJson('/api/venues');
        $response->assertOk();
    }

    public function test_booths_index(): void
    {
        $this->skipIfMissing('booths');
        $response = $this->getJson('/api/booths');
        $response->assertOk();
    }

    public function test_national_hub(): void
    {
        $this->skipIfMissing('counties');
        $response = $this->getJson('/api/national-hub');
        $response->assertOk();
    }

    public function test_semantic_search_validation(): void
    {
        $response = $this->getJson('/api/search/semantic');
        $response->assertStatus(422);
    }

    public function test_voice_search_validation(): void
    {
        $response = $this->postJson('/api/search/voice', []);
        $response->assertStatus(422);
    }

    public function test_ad_serve(): void
    {
        $this->skipIfMissing('ad_placements');
        $response = $this->getJson('/api/ads/serve/featured_placement');
        $response->assertOk();
    }

    public function test_ussd_callback(): void
    {
        $response = $this->postJson('/api/ussd/callback', [
            'sessionId' => 'test',
            'serviceCode' => '*123#',
            'phoneNumber' => '+254700000000',
            'text' => '',
        ]);
        $this->assertTrue(in_array($response->status(), [200, 401]));
    }

    public function test_health_check(): void
    {
        $response = $this->getJson('/up');
        $response->assertOk();
    }

    public function test_security_headers(): void
    {
        $response = $this->get('/api/counties');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_cors_headers(): void
    {
        $response = $this->getJson('/api/counties');
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }
}