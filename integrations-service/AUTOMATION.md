# KICC Inter-Pipeline Automation Layer

Proves and implements what the audit found missing: the 87 pipelines did not talk to
each other. This layer makes one pipeline's settled output automatically drive every
pipeline that depends on it, recursively, with contracts, backpressure and failure
containment.

## Files
| File | Role |
|---|---|
| `lib/bus.mjs` | Event bus: bounded queue (real backpressure), at-least-once delivery, exponential-backoff retry, dead-letter queue, idempotency dedupe, durable journal |
| `lib/contracts.mjs` | Typed event contracts — an event that does not validate cannot be published or consumed |
| `pipelines/graph.mjs` | Dependency graph built from real coupling signals (shared external system, shared owner desk, category stage order); DAG by construction, cycle detector retained as a guard |
| `pipelines/cascade.mjs` | Orchestrator: a settled pipeline publishes `pipeline.settled`, which triggers its dependents via `pipeline.trigger`, transitively, all under one correlation id |
| `server.mjs` | HTTP bridge so Laravel (`N8nService`, `IntegrationClient`) and the Node pipelines drive ONE graph |
| `run/test_cascade.mjs` | 31 end-to-end assertions; fails if any pipeline cannot drive the next |

## Run
```bash
node run/test_cascade.mjs          # 31 E2E cascade assertions
node server.mjs                    # bus HTTP surface on :8790
curl localhost:8790/api/pipeline/upstream?id=44
curl -X POST localhost:8790/api/pipeline/cascade -d '{"roots":[1]}' -H 'content-type: application/json'
```

## Laravel bridge
`app/Services/IntegrationClient.php` already has `baseUrl` + `Http::timeout()`; add:
```php
public function cascade(array $roots): array {
    return $this->call('POST', '/api/pipeline/cascade', ['roots' => $roots]);
}
public function pipelineGraph(): array { return $this->call('GET', '/api/pipeline/graph'); }
```
`N8nService::$events` (23 outbound events) stays as the outbound edge; the bus is the
inbound/cross-pipeline edge that was missing.
