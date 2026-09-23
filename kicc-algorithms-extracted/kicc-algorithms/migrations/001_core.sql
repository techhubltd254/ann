-- KICC algorithm layer — core tables (PostgreSQL/TiDB-compatible)
-- Maps 1:1 to core/ ports: idempotency, ledger journal, escrow, quality, pool.
CREATE TABLE IF NOT EXISTS idempotency_keys (
    key VARCHAR(255) PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS ledger_journal (
    seq BIGSERIAL PRIMARY KEY,
    entry_type VARCHAR(20) NOT NULL,          -- post | hold | release | cancel_hold
    debit_account VARCHAR(120),
    credit_account VARCHAR(120),
    account VARCHAR(120),
    destinations JSONB,
    amount NUMERIC(18,2) NOT NULL,
    ref VARCHAR(120),
    ts TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS escrow_transactions (
    id BIGSERIAL PRIMARY KEY,
    escrow_id VARCHAR(64) UNIQUE NOT NULL,
    order_id BIGINT NOT NULL,
    buyer_id BIGINT NOT NULL,
    seller_id BIGINT NOT NULL,
    gross NUMERIC(18,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'funded',  -- funded|released|disputed
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    released_at TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_escrow_seller ON escrow_transactions(seller_id, status);

CREATE TABLE IF NOT EXISTS commission_logs (
    id BIGSERIAL PRIMARY KEY,
    escrow_id VARCHAR(64),
    seller_id BIGINT NOT NULL,
    amount NUMERIC(18,2) NOT NULL,
    rate NUMERIC(6,4) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS quality_scores (
    id BIGSERIAL PRIMARY KEY,
    scoreable_type VARCHAR(50) NOT NULL,
    scoreable_id BIGINT NOT NULL,
    score NUMERIC(6,4) NOT NULL,
    components JSONB NOT NULL,
    algorithm_version VARCHAR(40) NOT NULL,
    computed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_quality_entity ON quality_scores(scoreable_type, scoreable_id);

CREATE TABLE IF NOT EXISTS pool_contributions (
    id BIGSERIAL PRIMARY KEY,
    entity_id BIGINT NOT NULL,
    pool_share NUMERIC(18,2) NOT NULL,
    county_id BIGINT,
    sector_id BIGINT,
    period_id VARCHAR(7) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_pool_period ON pool_contributions(period_id, entity_id);

CREATE TABLE IF NOT EXISTS pool_distributions (
    id BIGSERIAL PRIMARY KEY,
    entity_id BIGINT NOT NULL,
    period_id VARCHAR(7) NOT NULL,
    contribution_weight NUMERIC(10,6) NOT NULL,
    quality_weight NUMERIC(10,6) NOT NULL,
    final_weight NUMERIC(10,6) NOT NULL,
    amount NUMERIC(18,2) NOT NULL,
    breakdown JSONB NOT NULL,                -- full explainability trace
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    settled_at TIMESTAMPTZ
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_distribution ON pool_distributions(entity_id, period_id);

CREATE TABLE IF NOT EXISTS platform_stats_cache (
    key VARCHAR(64) PRIMARY KEY,
    payload JSONB NOT NULL,
    computed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS seasonal_calendar (
    county_code VARCHAR(10) NOT NULL,
    month INT NOT NULL,
    avg_temp_c NUMERIC(5,1),
    rainfall_mm NUMERIC(7,1),
    season_tag VARCHAR(12),
    PRIMARY KEY (county_code, month)
);
