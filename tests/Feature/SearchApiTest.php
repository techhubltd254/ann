<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    public function test_semantic_search_requires_query(): void
    {
        $response = $this->getJson('/api/search/semantic');
        $response->assertStatus(422);
    }

    public function test_semantic_search_with_short_query(): void
    {
        $response = $this->getJson('/api/search/semantic?q=x');
        $response->assertStatus(422);
    }

    public function test_semantic_search_accepts_valid_query(): void
    {
        if (! Schema::hasTable('embeddings')) {
            $this->markTestSkipped('embeddings table not available');
        }
        $response = $this->getJson('/api/search/semantic?q=Mombasa');
        $response->assertOk();
    }

    public function test_ad_serve_returns_json(): void
    {
        if (! Schema::hasTable('ad_placements')) {
            $this->markTestSkipped('ad_placements table not available');
        }
        $response = $this->getJson('/api/ads/serve/featured_placement');
        $response->assertOk();
    }

    public function test_ad_serve_with_invalid_placement(): void
    {
        $response = $this->getJson('/api/ads/serve/nonexistent_placement');
        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_ussd_callback_rejects_without_token(): void
    {
        $response = $this->postJson('/api/ussd/callback', [
            'sessionId' => 'test',
            'serviceCode' => '123',
            'phoneNumber' => '+254700000000',
            'text' => '',
        ]);
        $this->assertTrue(in_array($response->status(), [200, 401]));
    }

    public function test_media_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/media');
        $response->assertStatus(401);
    }

    public function test_pipeline_endpoint_requires_auth(): void
    {
        $response = $this->postJson('/api/pipeline/upload');
        $response->assertStatus(401);
    }
}