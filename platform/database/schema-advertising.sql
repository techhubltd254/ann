-- =============================================================
-- KICC Advertising Schema
-- Campaigns, creatives, targeting, impressions, analytics
-- 18 tables • Compatible: SQLite + MySQL/TiDB
-- =============================================================

-- ── 1. Advertisers (extended from users) ──
CREATE TABLE IF NOT EXISTS advertisers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    company_name VARCHAR(255) NOT NULL,
    company_registration VARCHAR(255),
    kra_pin VARCHAR(255),
    industry VARCHAR(100),
    website VARCHAR(255),
    phone VARCHAR(20),
    billing_address TEXT,
    account_balance REAL DEFAULT 0,
    credit_limit REAL DEFAULT 0,
    status VARCHAR(30) DEFAULT 'active',
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(user_id)
);

-- ── 2. Ad Campaigns ──
CREATE TABLE IF NOT EXISTS ad_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    advertiser_id INTEGER NOT NULL REFERENCES advertisers(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    objective VARCHAR(50) NOT NULL,
    status VARCHAR(30) DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    daily_budget REAL,
    total_budget REAL,
    currency VARCHAR(3) DEFAULT 'KES',
    spent REAL DEFAULT 0,
    target_impressions INTEGER,
    target_clicks INTEGER,
    target_conversions INTEGER,
    optimization_goal VARCHAR(50),
    is_cpm INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ac_advertiser ON ad_campaigns(advertiser_id);
CREATE INDEX IF NOT EXISTS_idx_ac_status ON ad_campaigns(status);

-- ── 3. Ad Groups ──
CREATE TABLE IF NOT EXISTS ad_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    bid_amount REAL NOT NULL,
    bid_strategy VARCHAR(30) DEFAULT 'auto',
    budget REAL,
    status VARCHAR(30) DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ag_campaign ON ad_groups(campaign_id);

-- ── 4. Ad Creatives ──
CREATE TABLE IF NOT EXISTS ad_creatives (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ad_group_id INTEGER NOT NULL REFERENCES ad_groups(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'image',
    headline VARCHAR(255),
    description TEXT,
    call_to_action VARCHAR(100),
    destination_url VARCHAR(255) NOT NULL,
    image_url VARCHAR(255),
    video_url VARCHAR(255),
    width INTEGER,
    height INTEGER,
    alt_text VARCHAR(255),
    status VARCHAR(30) DEFAULT 'pending_review',
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at DATETIME,
    rejection_reason TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_acr_group ON ad_creatives(ad_group_id);

-- ── 5. Ad Placements (slot definitions) ──
CREATE TABLE IF NOT EXISTS ad_placements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    page VARCHAR(100) NOT NULL,
    position VARCHAR(50) NOT NULL,
    width INTEGER,
    height INTEGER,
    max_ads INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 6. Ad Targeting Rules ──
CREATE TABLE IF NOT EXISTS ad_targeting (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    creative_id INTEGER NOT NULL REFERENCES ad_creatives(id) ON DELETE CASCADE,
    age_min INTEGER,
    age_max INTEGER,
    genders VARCHAR(50),
    county_ids TEXT,
    sectors TEXT,
    interests TEXT,
    devices TEXT,
    browsers TEXT,
    operating_systems TEXT,
    connection_types TEXT,
    hours_of_day TEXT,
    days_of_week TEXT,
    frequency_cap INTEGER,
    frequency_cap_period VARCHAR(20) DEFAULT 'day',
    geolocation_radius_km REAL,
    geolocation_lat REAL,
    geolocation_lng REAL,
    custom_audience_ids TEXT,
    excluded_audience_ids TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 7. Ad Impressions (high-volume, partitioned by date) ──
CREATE TABLE IF NOT EXISTS ad_impressions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    creative_id INTEGER NOT NULL REFERENCES ad_creatives(id) ON DELETE CASCADE,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    placement_id INTEGER REFERENCES ad_placements(id) ON DELETE SET NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer_url VARCHAR(255),
    page_url VARCHAR(255),
    device_type VARCHAR(30),
    browser VARCHAR(50),
    os VARCHAR(50),
    country VARCHAR(100),
    region VARCHAR(100),
    city VARCHAR(100),
    latitude REAL,
    longitude REAL,
    bid_amount REAL,
    win_amount REAL,
    served_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ai_creative ON ad_impressions(creative_id);
CREATE INDEX IF NOT EXISTS_idx_ai_campaign ON ad_impressions(campaign_id);
CREATE INDEX IF NOT EXISTS_idx_ai_served ON ad_impressions(served_at);
CREATE INDEX IF NOT EXISTS_idx_ai_user ON ad_impressions(user_id);

-- ── 8. Ad Clicks ──
CREATE TABLE IF NOT EXISTS ad_clicks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    impression_id INTEGER NOT NULL REFERENCES ad_impressions(id) ON DELETE CASCADE,
    creative_id INTEGER NOT NULL REFERENCES ad_creatives(id) ON DELETE CASCADE,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    clicked_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_aclick_impression ON ad_clicks(impression_id);
CREATE INDEX IF NOT EXISTS_idx_aclick_creative ON ad_clicks(creative_id);

-- ── 9. Ad Conversions ──
CREATE TABLE IF NOT EXISTS ad_conversions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    click_id INTEGER REFERENCES ad_clicks(id) ON DELETE SET NULL,
    impression_id INTEGER REFERENCES ad_impressions(id) ON DELETE SET NULL,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    conversion_type VARCHAR(50) NOT NULL,
    value REAL,
    currency VARCHAR(3) DEFAULT 'KES',
    reference_type VARCHAR(50),
    reference_id INTEGER,
    converted_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_aconv_campaign ON ad_conversions(campaign_id);
CREATE INDEX IF NOT EXISTS_idx_aconv_type ON ad_conversions(conversion_type);

-- ── 10. Ad Budget Pacing ──
CREATE TABLE IF NOT EXISTS ad_budget_pacing (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    daily_budget REAL,
    spent REAL DEFAULT 0,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(campaign_id, date)
);

-- ── 11. Ad Publisher Payouts ──
CREATE TABLE IF NOT EXISTS ad_publisher_payouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    publisher_type VARCHAR(50) NOT NULL,
    publisher_id INTEGER NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    revenue REAL DEFAULT 0,
    commission_rate REAL,
    commission_amount REAL,
    status VARCHAR(30) DEFAULT 'pending',
    paid_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 12. Ad Analytics Reports (pre-aggregated) ──
CREATE TABLE IF NOT EXISTS ad_analytics_daily (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    ad_group_id INTEGER REFERENCES ad_groups(id) ON DELETE SET NULL,
    creative_id INTEGER REFERENCES ad_creatives(id) ON DELETE SET NULL,
    date DATE NOT NULL,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    spend REAL DEFAULT 0,
    revenue REAL DEFAULT 0,
    ctr REAL DEFAULT 0,
    cpc REAL DEFAULT 0,
    cpm REAL DEFAULT 0,
    roas REAL DEFAULT 0,
    created_at DATETIME,
    UNIQUE(campaign_id, date, creative_id)
);

-- ── 13. Programmatic Bids ──
CREATE TABLE IF NOT EXISTS ad_programmatic_bids (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    creative_id INTEGER NOT NULL REFERENCES ad_creatives(id) ON DELETE CASCADE,
    placement_id INTEGER NOT NULL REFERENCES ad_placements(id) ON DELETE CASCADE,
    bid_amount REAL NOT NULL,
    win_amount REAL,
    is_win INTEGER DEFAULT 0,
    auction_id VARCHAR(100) NOT NULL UNIQUE,
    auctioned_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_apb_creative ON ad_programmatic_bids(creative_id);

-- ── 14. Advertiser Payment Methods ──
CREATE TABLE IF NOT EXISTS advertiser_payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    advertiser_id INTEGER NOT NULL REFERENCES advertisers(id) ON DELETE CASCADE,
    type VARCHAR(30) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    account_name VARCHAR(255),
    account_number VARCHAR(255),
    phone_number VARCHAR(20),
    is_default INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 15. Advertiser Transactions ──
CREATE TABLE IF NOT EXISTS advertiser_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    advertiser_id INTEGER NOT NULL REFERENCES advertisers(id) ON DELETE CASCADE,
    transaction_id VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(30) NOT NULL,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    balance_before REAL,
    balance_after REAL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    description TEXT,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_at_advertiser ON advertiser_transactions(advertiser_id);

-- =============================================================
-- END OF ADVERTISING SCHEMA — 15 tables
-- =============================================================
