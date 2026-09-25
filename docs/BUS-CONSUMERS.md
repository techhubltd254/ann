# Inter-pipeline bus consumers (kicc_algorithms, ml-engine)

The bus journal `integrations-service/run/artifacts/bus.jsonl` is the queue. Each
consumer tails it with its own durable checkpoint, so a restart resumes instead of
losing the chain.

    pipeline.settled ──▶ kicc-algorithms ──▶ ml.inference.requested ──▶ ml-engine ──▶ ml.prediction

## Run

    node integrations-service/consumers/run_consumers.mjs                    # both groups
    node integrations-service/consumers/run_consumers.mjs --only=ml-engine   # one group
    curl -s http://127.0.0.1:8791/api/consumers/status | jq .consumers

Install as services: `infra/supervisor/kicc-consumers.conf` or
`docker compose -f infra/docker-compose.consumers.yml up -d`.

## Behaviour

| Guarantee | Where |
|---|---|
| ack after success only | `lib/consumer.mjs` `#attempt` → `#ack` |
| bounded retries + backoff | `maxAttempts`, `backoffMs` (exp. 20/40/80 ms) |
| dead-letter, never dropped | `<state_dir>/dlq.jsonl` |
| idempotency (redelivery skipped) | `<state_dir>/terminal.keys` (sha1, 40 hex) |
| backpressure | `maxInFlight` caps concurrent handlers |
| graceful shutdown | SIGTERM/SIGINT → drain → flush checkpoint → exit 0 |
| observability | `/api/consumers/status` (lag, offset, retries, dlq) |

## Test

    node integrations-service/run/test_consumers.mjs

Covers the full chain, a poison event (3 retries → DLQ), a crash/redelivery replay
(0 reprocessing), a 500-event concurrency burst, checkpoint persistence and
graceful shutdown.
