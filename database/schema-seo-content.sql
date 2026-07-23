-- =============================================================
-- KICC SEO & Content Management Schema
-- CMS pages, SEO metadata, redirects, sitemaps, analytics
-- 15 tables • Compatible: SQLite + MySQL/TiDB
-- =============================================================

-- ── 1. Content Pages (CMS) ──
CREATE TABLE IF NOT EXISTS content_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT,
    excerpt TEXT,
    template VARCHAR(100) DEFAULT 'default',
    cover_image VARCHAR(255),
    author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(30) DEFAULT 'draft',
    published_at DATETIME,
    is_featured INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_cp_slug ON content_pages(slug);
CREATE INDEX IF NOT EXISTS_idx_cp_status ON content_pages(status);

-- ── 2. Content Blocks (reusable) ──
CREATE TABLE IF NOT EXISTS content_blocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'html',
    content TEXT NOT NULL,
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 3. SEO Metadata ──
CREATE TABLE IF NOT EXISTS seo_metadata (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    seoable_type VARCHAR(50) NOT NULL,
    seoable_id INTEGER NOT NULL,
    title VARCHAR(255),
    description TEXT,
    keywords TEXT,
    og_title VARCHAR(255),
    og_description TEXT,
    og_image VARCHAR(255),
    og_type VARCHAR(50) DEFAULT 'website',
    canonical_url VARCHAR(255),
    no_index INTEGER DEFAULT 0,
    no_follow INTEGER DEFAULT 0,
    structured_data TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(seoable_type, seoable_id)
);

-- ── 4. Redirect Rules ──
CREATE TABLE IF NOT EXISTS redirect_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    old_url VARCHAR(255) NOT NULL,
    new_url VARCHAR(255) NOT NULL,
    status_code INTEGER DEFAULT 301,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_rr_old ON redirect_rules(old_url);

-- ── 5. Sitemap URLs ──
CREATE TABLE IF NOT EXISTS sitemap_urls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url VARCHAR(255) NOT NULL UNIQUE,
    change_frequency VARCHAR(20) DEFAULT 'weekly',
    priority REAL DEFAULT 0.5,
    last_modified DATETIME,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 6. Search Queries (internal search log) ──
CREATE TABLE IF NOT EXISTS search_queries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    query VARCHAR(255) NOT NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    session_id VARCHAR(255),
    result_count INTEGER DEFAULT 0,
    clicked_item_type VARCHAR(50),
    clicked_item_id INTEGER,
    duration_ms INTEGER,
    ip_address VARCHAR(45),
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_sq_query ON search_queries(query);
CREATE INDEX IF NOT EXISTS_idx_sq_created ON search_queries(created_at);

-- ── 7. Tags (unified tagging) ──
CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(50) DEFAULT 'general',
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 8. Taggables (polymorphic pivot) ──
CREATE TABLE IF NOT EXISTS taggables (
    tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    taggable_type VARCHAR(50) NOT NULL,
    taggable_id INTEGER NOT NULL,
    created_at DATETIME,
    PRIMARY KEY(tag_id, taggable_type, taggable_id)
);
CREATE INDEX IF NOT EXISTS_idx_taggable ON taggables(taggable_type, taggable_id);

-- ── 9. News / Announcements ──
CREATE TABLE IF NOT EXISTS news_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT,
    excerpt TEXT,
    cover_image VARCHAR(255),
    author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    category VARCHAR(50) DEFAULT 'general',
    status VARCHAR(30) DEFAULT 'draft',
    published_at DATETIME,
    is_featured INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_np_slug ON news_posts(slug);
CREATE INDEX IF NOT EXISTS_idx_np_status ON news_posts(status);

-- ── 10. Analytics Page Views ──
CREATE TABLE IF NOT EXISTS page_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer_url VARCHAR(255),
    device_type VARCHAR(30),
    browser VARCHAR(50),
    os VARCHAR(50),
    country VARCHAR(100),
    region VARCHAR(100),
    city VARCHAR(100),
    duration_sec INTEGER DEFAULT 0,
    viewed_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_pv_url ON page_views(url);
CREATE INDEX IF NOT EXISTS_idx_pv_viewed ON page_views(viewed_at);
CREATE INDEX IF NOT EXISTS_idx_pv_session ON page_views(session_id);

-- ── 11. Analytics Events ──
CREATE TABLE IF NOT EXISTS analytics_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    session_id VARCHAR(255),
    event_type VARCHAR(50) NOT NULL,
    event_category VARCHAR(50),
    event_action VARCHAR(100),
    event_label VARCHAR(255),
    event_value REAL,
    page_url VARCHAR(255),
    metadata TEXT,
    occurred_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ae_type ON analytics_events(event_type);
CREATE INDEX IF NOT EXISTS_idx_ae_session ON analytics_events(session_id);
CREATE INDEX IF NOT EXISTS_idx_ae_occurred ON analytics_events(occurred_at);

-- ── 12. Page Speed Results ──
CREATE TABLE IF NOT EXISTS page_speed_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url VARCHAR(255) NOT NULL,
    performance_score REAL,
    accessibility_score REAL,
    best_practices_score REAL,
    seo_score REAL,
    lcp_ms INTEGER,
    cls REAL,
    ttf_ms INTEGER,
    tbt_ms INTEGER,
    test_location VARCHAR(50),
    device_type VARCHAR(20),
    tested_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_psr_url ON page_speed_results(url);

-- ── 13. Performance KPIs ──
CREATE TABLE IF NOT EXISTS performance_kpis (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    metric_name VARCHAR(100) NOT NULL,
    metric_value REAL NOT NULL,
    unit VARCHAR(50),
    source VARCHAR(50) DEFAULT 'system',
    recorded_at DATE NOT NULL,
    created_at DATETIME,
    UNIQUE(metric_name, recorded_at)
);
CREATE INDEX IF NOT EXISTS_idx_pk_metric ON performance_kpis(metric_name);
CREATE INDEX IF NOT EXISTS_idx_pk_date ON performance_kpis(recorded_at);

-- =============================================================
-- END OF SEO & CONTENT SCHEMA — 13 tables
-- =============================================================
