-- =============================================================
-- KICC Payments & Subscriptions Schema (Expanded)
-- Gateways, payouts, settlements, refunds, disputes, billing
-- 20 tables • Compatible: SQLite + MySQL/TiDB
-- =============================================================

-- ── 1. Payment Gateways Config ──
CREATE TABLE IF NOT EXISTS payment_gateways (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    provider_class VARCHAR(255) NOT NULL,
    is_active INTEGER DEFAULT 1,
    config JSON,
    supported_currencies TEXT,
    supported_methods TEXT,
    min_amount REAL,
    max_amount REAL,
    fee_percentage REAL DEFAULT 0,
    fee_fixed REAL DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 2. Payment Intents (unified payment record) ──
CREATE TABLE IF NOT EXISTS payment_intents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    intent_id VARCHAR(255) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    gateway_id INTEGER REFERENCES payment_gateways(id) ON DELETE SET NULL,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    reference_type VARCHAR(50) NOT NULL,
    reference_id INTEGER NOT NULL,
    description VARCHAR(255),
    metadata TEXT,
    confirmed_at DATETIME,
    failed_at DATETIME,
    failure_reason TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_pi_user ON payment_intents(user_id);
CREATE INDEX IF NOT EXISTS_idx_pi_reference ON payment_intents(reference_type, reference_id);
CREATE INDEX IF NOT EXISTS_idx_pi_status ON payment_intents(status);

-- ── 3. Transaction Logs (raw gateway data) ──
CREATE TABLE IF NOT EXISTS transaction_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_intent_id INTEGER REFERENCES payment_intents(id) ON DELETE SET NULL,
    gateway_id INTEGER REFERENCES payment_gateways(id) ON DELETE SET NULL,
    transaction_id VARCHAR(255),
    type VARCHAR(30) NOT NULL,
    request_payload TEXT,
    response_payload TEXT,
    status_code INTEGER,
    duration_ms INTEGER,
    ip_address VARCHAR(45),
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_tl_intent ON transaction_logs(payment_intent_id);

-- ── 4. Refunds ──
CREATE TABLE IF NOT EXISTS refunds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    refund_id VARCHAR(255) NOT NULL UNIQUE,
    payment_intent_id INTEGER NOT NULL REFERENCES payment_intents(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    reason VARCHAR(255),
    status VARCHAR(30) DEFAULT 'pending',
    gateway_reference VARCHAR(255),
    processed_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ref_payment ON refunds(payment_intent_id);

-- ── 5. Disputes ──
CREATE TABLE IF NOT EXISTS payment_disputes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dispute_id VARCHAR(255) NOT NULL UNIQUE,
    payment_intent_id INTEGER NOT NULL REFERENCES payment_intents(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    reason VARCHAR(100) NOT NULL,
    description TEXT,
    evidence TEXT,
    status VARCHAR(30) DEFAULT 'open',
    gateway_reference VARCHAR(255),
    resolved_at DATETIME,
    resolved_by INTEGER REFERENCES users(id),
    resolution_notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 6. Seller Payouts ──
CREATE TABLE IF NOT EXISTS seller_payouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payout_id VARCHAR(255) NOT NULL UNIQUE,
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    commission_deducted REAL DEFAULT 0,
    status VARCHAR(30) DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    period_start DATE,
    period_end DATE,
    processed_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_sp_supplier ON seller_payouts(supplier_id);
CREATE INDEX IF NOT EXISTS_idx_sp_status ON seller_payouts(status);

-- ── 7. Settlement Batches ──
CREATE TABLE IF NOT EXISTS settlement_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_reference VARCHAR(255) NOT NULL UNIQUE,
    gateway_id INTEGER REFERENCES payment_gateways(id) ON DELETE SET NULL,
    total_amount REAL NOT NULL,
    total_transactions INTEGER NOT NULL,
    fee_amount REAL DEFAULT 0,
    net_amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    settled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 8. Settlement Transactions ──
CREATE TABLE IF NOT EXISTS settlement_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    settlement_batch_id INTEGER NOT NULL REFERENCES settlement_batches(id) ON DELETE CASCADE,
    payment_intent_id INTEGER NOT NULL REFERENCES payment_intents(id) ON DELETE CASCADE,
    transaction_amount REAL NOT NULL,
    fee_amount REAL DEFAULT 0,
    net_amount REAL NOT NULL,
    created_at DATETIME
);

-- ── 9. Subscription Features ──
CREATE TABLE IF NOT EXISTS subscription_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(30) DEFAULT 'boolean',
    unit_label VARCHAR(50),
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 10. Subscription Plan Features ──
CREATE TABLE IF NOT EXISTS subscription_plan_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_id INTEGER NOT NULL REFERENCES subscription_plans(id) ON DELETE CASCADE,
    feature_id INTEGER NOT NULL REFERENCES subscription_features(id) ON DELETE CASCADE,
    value VARCHAR(255) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(plan_id, feature_id)
);

-- ── 11. Usage Logs ──
CREATE TABLE IF NOT EXISTS usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_id INTEGER REFERENCES user_subscriptions(id) ON DELETE SET NULL,
    feature_code VARCHAR(50) NOT NULL,
    quantity INTEGER DEFAULT 1,
    unit VARCHAR(50),
    metadata TEXT,
    recorded_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ul_user ON usage_logs(user_id);
CREATE INDEX IF NOT EXISTS_idx_ul_feature ON usage_logs(feature_code);
CREATE INDEX IF NOT EXISTS_idx_ul_recorded ON usage_logs(recorded_at);

-- ── 12. Billing Cycles ──
CREATE TABLE IF NOT EXISTS billing_cycles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subscription_id INTEGER NOT NULL REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    cycle_number INTEGER NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    paid_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_bc_subscription ON billing_cycles(subscription_id);

-- ── 13. Invoices ──
CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_id INTEGER REFERENCES user_subscriptions(id) ON DELETE SET NULL,
    billing_cycle_id INTEGER REFERENCES billing_cycles(id) ON DELETE SET NULL,
    type VARCHAR(30) DEFAULT 'subscription',
    status VARCHAR(30) DEFAULT 'draft',
    subtotal REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    due_date DATE,
    paid_at DATETIME,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_inv_user ON invoices(user_id);
CREATE INDEX IF NOT EXISTS_idx_inv_status ON invoices(status);

-- ── 14. Invoice Items ──
CREATE TABLE IF NOT EXISTS invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    description VARCHAR(255) NOT NULL,
    quantity INTEGER DEFAULT 1,
    unit_price REAL NOT NULL,
    tax_rate REAL DEFAULT 0,
    tax_amount REAL DEFAULT 0,
    total REAL NOT NULL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    created_at DATETIME
);

-- ── 15. Tax Rates ──
CREATE TABLE IF NOT EXISTS tax_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    rate REAL NOT NULL,
    type VARCHAR(30) DEFAULT 'vat',
    applies_to VARCHAR(50) DEFAULT 'all',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 16. Currency Exchange Rates ──
CREATE TABLE IF NOT EXISTS currency_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    base_currency VARCHAR(3) NOT NULL DEFAULT 'KES',
    target_currency VARCHAR(3) NOT NULL,
    rate REAL NOT NULL,
    source VARCHAR(50) DEFAULT 'manual',
    valid_from DATE NOT NULL,
    valid_to DATE,
    created_at DATETIME,
    UNIQUE(base_currency, target_currency, valid_from)
);

-- =============================================================
-- END OF PAYMENTS SCHEMA — 16 tables
-- =============================================================
