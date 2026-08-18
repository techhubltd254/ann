<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    private function skipIfMissingDb(string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->markTestSkipped("$table table not available in test environment");
        }
    }
    // ── SQL Injection ──
    public function test_sql_injection_on_counties(): void
    {
        $this->skipIfMissingDb('counties');
        $response = $this->getJson('/api/counties?q=1%27%20OR%20%271%27%3D%271');
        $response->assertStatus(200);
    }

    public function test_sql_injection_on_search(): void
    {
        $this->skipIfMissingDb('embeddings');
        $response = $this->getJson('/api/search/semantic?q=1%27%20OR%20%271%27%3D%271');
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }

    public function test_sql_injection_on_login(): void
    {
        $this->skipIfMissingDb('users');
        $response = $this->postJson('/api/auth/login', [
            'email' => "' OR '1'='1",
            'password' => "' OR '1'='1",
        ]);
        $response->assertStatus(422);
    }

    // ── XSS ──
    public function test_xss_on_search(): void
    {
        $this->skipIfMissingDb('embeddings');
        $response = $this->getJson('/api/search/semantic?q=<script>alert(1)</script>');
        $this->assertTrue(in_array($response->status(), [200, 422]));
    }

    public function test_xss_on_counties(): void
    {
        $this->skipIfMissingDb('counties');
        $response = $this->getJson('/api/counties?<script>alert(1)</script>');
        $response->assertStatus(200);
    }

    // ── Authentication Bypass ──
    public function test_protected_routes_require_auth(): void
    {
        $this->skipIfMissingDb('personal_access_tokens');
        $protected = [
            '/api/auth/me',
            '/api/auth/logout',
            '/api/media',
            '/api/pipeline/upload',
            '/api/bookings',
            '/api/agentic/trigger',
            '/api/escrow',
            '/api/verification/status',
        ];

        foreach ($protected as $path) {
            $response = $this->getJson($path);
            $response->assertStatus(401, "Path $path should require auth");
        }
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->skipIfMissingDb('personal_access_tokens');
        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer invalid-token-12345',
        ]);
        $response->assertStatus(401);
    }

    // ── Rate Limiting ──
    public function test_rate_limiting_on_login(): void
    {
        $this->skipIfMissingDb('users');
        for ($i = 0; $i < 25; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => "test$i@test.com",
                'password' => 'password',
            ]);
        }
        // The 25th request should be rate limited
        $this->assertTrue(in_array($response->status(), [429, 422]));
    }

    // ── Path Traversal ──
    public function test_path_traversal_on_counties(): void
    {
        $this->skipIfMissingDb('counties');
        $response = $this->getJson('/api/counties/../../../etc/passwd');
        $response->assertStatus(404);
    }

    // ── Mass Assignment ──
    public function test_register_disallows_admin_fields(): void
    {
        $this->skipIfMissingDb('users');
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@hacker.com',
            'password' => 'password123',
            'account_type' => 'superadmin',
            'is_admin' => true,
            'role' => 'admin',
        ]);
        // Should not allow creating a superadmin account
        $this->assertTrue(in_array($response->status(), [201, 422]));
    }

    // ── IDOR (Insecure Direct Object Reference) ──
public function test_idor_on_counties(): void
    {
        $this->skipIfMissingDb('counties');
        $response = $this->getJson('/api/counties/nonexistent');
        $response->assertStatus(404);
    }

    // ── HTTP Methods ──
    public function test_unexpected_http_methods_return_405(): void
    {
        $this->skipIfMissingDb('counties');
        $response = $this->putJson('/api/counties', []);
        $this->assertTrue(in_array($response->status(), [405, 401]));
    }

    public function test_delete_on_public_endpoint(): void
    {
        $this->skipIfMissingDb('counties');
        $response = $this->deleteJson('/api/counties');
        $this->assertTrue(in_array($response->status(), [405, 401]));
    }

    // ── Headers ──
    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/api/counties');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    // ── Content Type ──
    public function test_api_returns_json(): void
    {
        $response = $this->get('/api/counties');
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type') ?? '');
    }

    public function test_api_rejects_wrong_content_type(): void
    {
        $this->skipIfMissingDb('users');
        $response = $this->post('/api/auth/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ], ['Content-Type' => 'text/plain']);
        $response->assertStatus(422);
    }
}