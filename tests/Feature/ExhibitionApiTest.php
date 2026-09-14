<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExhibitionApiTest extends TestCase
{
    public function test_public_routes_are_stateless(): void
    {
        $response = $this->get('/api/counties');
        $response->assertHeaderMissing('set-cookie');
    }

    public function test_cors_headers_on_api(): void
    {
        $response = $this->getJson('/api/counties');
        $response->assertHeader('Access-Control-Allow-Origin', config('app.url'));
    }
}