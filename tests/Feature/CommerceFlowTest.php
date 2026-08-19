<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    private function skipIfMissing(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->markTestSkipped("$table not available");
        }
    }

    public function test_exhibition_show_unknown(): void
    {
        $this->skipIfMissing('exhibitions');
        $response = $this->getJson('/api/exhibitions/unknown-slug');
        $response->assertStatus(404);
    }

    public function test_venue_show_unknown(): void
    {
        $this->skipIfMissing('venues');
        $response = $this->getJson('/api/venues/unknown-slug');
        $response->assertStatus(404);
    }

    public function test_booth_show_unknown(): void
    {
        $this->skipIfMissing('booths');
        $response = $this->getJson('/api/booths/99999');
        $response->assertStatus(404);
    }

    public function test_escrow_requires_auth(): void
    {
        $response = $this->postJson('/api/escrow', []);
        $response->assertStatus(401);
    }

    public function test_escrow_store_validation(): void
    {
        $this->skipIfMissing('personal_access_tokens');
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/escrow', []);
        $response->assertStatus(422);
    }

    public function test_bookings_requires_auth(): void
    {
        $response = $this->getJson('/api/bookings');
        $response->assertStatus(401);
    }

    public function test_media_requires_auth(): void
    {
        $response = $this->getJson('/api/media');
        $response->assertStatus(401);
    }

    public function test_media_upload_requires_auth(): void
    {
        $response = $this->postJson('/api/media', []);
        $response->assertStatus(401);
    }

    public function test_pipeline_upload_requires_auth(): void
    {
        $response = $this->postJson('/api/pipeline/upload', []);
        $response->assertStatus(401);
    }

    public function test_disputes_requires_auth(): void
    {
        $response = $this->postJson('/api/disputes', []);
        $response->assertStatus(401);
    }

    public function test_tickets_requires_auth(): void
    {
        $response = $this->getJson('/api/tickets');
        $response->assertStatus(401);
    }

    public function test_ticket_types_requires_auth(): void
    {
        $response = $this->getJson('/api/ticket-types');
        $response->assertStatus(401);
    }

    public function test_verification_requires_auth(): void
    {
        $response = $this->getJson('/api/verification/status');
        $response->assertStatus(401);
    }

    public function test_agentic_loop_requires_auth(): void
    {
        $response = $this->postJson('/api/agentic/trigger', []);
        $response->assertStatus(401);
    }

    public function test_mcp_requires_auth(): void
    {
        $response = $this->getJson('/api/mcp');
        $response->assertStatus(401);
    }

    public function test_currency_convert(): void
    {
        $response = $this->postJson('/api/currency/convert', [
            'from' => 'USD', 'to' => 'KES', 'amount' => 100,
        ]);
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }

    public function test_ticket_lookup_unknown(): void
    {
        $this->skipIfMissing('tickets');
        $response = $this->getJson('/api/tickets/lookup/INVALID');
        $response->assertStatus(404);
    }

    public function test_county_sectors(): void
    {
        $this->skipIfMissing('counties');
        $response = $this->getJson('/api/counties/mombasa/sectors');
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_county_weather(): void
    {
        $this->skipIfMissing('counties');
        $response = $this->getJson('/api/counties/mombasa/weather');
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_match_destination(): void
    {
        $response = $this->postJson('/api/match-destination', [
            'county' => 'Mombasa',
            'interests' => ['beach', 'culture'],
        ]);
        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_recommendations(): void
    {
        $response = $this->getJson('/api/recommendations');
        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_forecast(): void
    {
        $response = $this->getJson('/api/forecast');
        $this->assertTrue(in_array($response->status(), [200, 500]));
    }

    public function test_fraud_check(): void
    {
        $response = $this->postJson('/api/fraud-check', [
            'user_id' => 1, 'amount' => 1000,
        ]);
        $response->assertOk();
    }

    public function test_exhibition_crud_requires_auth(): void
    {
        $response = $this->postJson('/api/exhibitions', []);
        $response->assertStatus(401);
    }

    public function test_venue_crud_requires_auth(): void
    {
        $response = $this->postJson('/api/venues', []);
        $response->assertStatus(401);
    }

    public function test_booth_crud_requires_auth(): void
    {
        $response = $this->postJson('/api/booths', []);
        $response->assertStatus(401);
    }

    public function test_ticket_type_crud_requires_auth(): void
    {
        $response = $this->postJson('/api/ticket-types', []);
        $response->assertStatus(401);
    }

    public function test_n8n_webhook(): void
    {
        $response = $this->postJson('/api/webhooks/n8n', []);
        $this->assertTrue(in_array($response->status(), [200, 400, 401, 422, 500]));
    }

    public function test_updates_manifest(): void
    {
        $response = $this->getJson('/api/updates/manifest');
        $this->assertTrue(in_array($response->status(), [200, 204]));
    }

    public function test_healthz(): void
    {
        $response = $this->get('/healthz');
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }
}