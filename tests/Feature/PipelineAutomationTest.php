<?php

namespace Tests\Feature;

use App\Filament\Pages\AutomationConsole;
use App\Services\AutomationTreeService;
use App\Services\PipelineBusClient;
use App\Support\RetryPolicy;
use Illuminate\Support\Facades\Http;
use ReflectionClass;

class PipelineAutomationTest extends TestCase
{
    /** The Mother Admin console must exist and be reachable by an admin account. */
    public function test_mother_admin_console_page_exists(): void
    {
        $this->assertTrue(class_exists(AutomationConsole::class));
        // Filament v5 declares the view as a protected instance property.
        $ref = new ReflectionClass(AutomationConsole::class);
        $prop = $ref->getProperty('view');
        $this->assertSame('filament.pages.automation-console', $prop->getValue(new AutomationConsole()));
    }

    /** The automation tree must be executable: every node carries real pipeline ids. */
    public function test_automation_tree_nodes_are_wired_to_pipelines(): void
    {
        $nodes = app(AutomationTreeService::class)->nodes();

        $this->assertCount(4, $nodes);
        foreach ($nodes as $n) {
            $this->assertNotEmpty($n['pipeline_ids'], "node {$n['key']} has no pipeline ids");
            $this->assertIsArray($n['pipeline_ids']);
            $this->assertGreaterThan(0, $n['actions']);
        }
        $this->assertSame(['county-content', 'trade-promotion', 'sbs', 'agentic-loop'], array_column($nodes, 'key'));
    }

    /** The bus client retries a 5xx and then succeeds, sending a stable idempotency key. */
    public function test_bus_client_retries_5xx_and_reuses_the_idempotency_key(): void
    {
        $keys = [];
        Http::fake(function ($request) use (&$keys) {
            $keys[] = $request->header('X-Idempotency-Key')[0] ?? null;

            return count($keys) < 3
                ? Http::response(['error' => 'boom'], 503)
                : Http::response(['ok' => true, 'pipelines' => [44, 45]], 200);
        });

        $client = new PipelineBusClient('http://bus.test', new RetryPolicy(3, 1, 5, 2.0));
        $out = $client->cascade([1]);

        $this->assertTrue($out['ok']);
        $this->assertSame([44, 45], $out['pipelines']);
        $this->assertCount(3, $keys, 'expected 3 HTTP attempts');
        $this->assertCount(1, array_unique($keys), 'every retry must reuse the same idempotency key');
    }

    /** A 4xx is a client fault: no retry, one attempt, reported not thrown. */
    public function test_bus_client_does_not_retry_a_4xx(): void
    {
        Http::fake([ '*' => Http::response(['error' => 'bad'], 422) ]);

        $client = new PipelineBusClient('http://bus.test', new RetryPolicy(3, 1, 5, 2.0));
        $out = $client->trigger(1);

        $this->assertFalse($out['ok']);
        $this->assertSame(1, $out['attempts']);
        $this->assertStringContainsString('422', $out['error']);
    }

    /** An unreachable bus must degrade gracefully, never throw. */
    public function test_bus_client_degrades_when_unreachable(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('refused'));

        $client = new PipelineBusClient('http://127.0.0.1:9', new RetryPolicy(2, 1, 2, 2.0));
        $out = $client->status();

        $this->assertFalse($out['ok']);
        $this->assertSame(2, $out['attempts']);
        $this->assertArrayHasKey('idempotency_key', $out);
    }

    /** IntegrationClient must expose the cascade edge (the fix that was missing). */
    public function test_integration_client_exposes_the_cascade_edge(): void
    {
        Http::fake([ '*' => Http::response(['ok' => true, 'pipelines' => [1, 2, 3]], 200) ]);

        $out = app(\App\Services\IntegrationClient::class)->cascade([1]);

        $this->assertTrue($out['ok']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/pipeline/cascade'));
    }
}
