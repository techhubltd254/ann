-- =============================================================
-- KICC AI Pipeline Schema
-- ML models, embeddings, recommendations, job tracking
-- 20 tables • Compatible: SQLite + MySQL/TiDB
-- =============================================================

-- ── 1. ML Models Registry ──
CREATE TABLE IF NOT EXISTS ml_models (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    model_type VARCHAR(50) NOT NULL,
    framework VARCHAR(50),
    version VARCHAR(50),
    storage_path VARCHAR(255),
    input_schema TEXT,
    output_schema TEXT,
    accuracy_metrics TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 2. Model Versions ──
CREATE TABLE IF NOT EXISTS model_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ml_model_id INTEGER NOT NULL REFERENCES ml_models(id) ON DELETE CASCADE,
    version VARCHAR(50) NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    metrics TEXT,
    training_data_count INTEGER,
    training_duration_sec REAL,
    is_production INTEGER DEFAULT 0,
    created_at DATETIME,
    UNIQUE(ml_model_id, version)
);

-- ── 3. Embeddings ──
CREATE TABLE IF NOT EXISTS embeddings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    embeddable_type VARCHAR(50) NOT NULL,
    embeddable_id INTEGER NOT NULL,
    model_id INTEGER NOT NULL REFERENCES ml_models(id) ON DELETE CASCADE,
    vector BLOB NOT NULL,
    dimension INTEGER NOT NULL,
    metadata TEXT,
    created_at DATETIME,
    UNIQUE(embeddable_type, embeddable_id, model_id)
);
CREATE INDEX IF NOT EXISTS idx_embeddings_type ON embeddings(embeddable_type, embeddable_id);

-- ── 4. User Segments (ML-generated) ──
CREATE TABLE IF NOT EXISTS user_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    criteria TEXT,
    is_dynamic INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 5. User Segment Assignments ──
CREATE TABLE IF NOT EXISTS user_segment_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    segment_id INTEGER NOT NULL REFERENCES user_segments(id) ON DELETE CASCADE,
    score REAL,
    assigned_by VARCHAR(50) DEFAULT 'ml',
    expires_at DATETIME,
    created_at DATETIME,
    UNIQUE(user_id, segment_id)
);
CREATE INDEX IF NOT EXISTS idx_usa_segment ON user_segment_assignments(segment_id);

-- ── 6. Match Scores (user → item recommendations) ──
CREATE TABLE IF NOT EXISTS match_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    matchable_type VARCHAR(50) NOT NULL,
    matchable_id INTEGER NOT NULL,
    score REAL NOT NULL,
    model_id INTEGER REFERENCES ml_models(id) ON DELETE SET NULL,
    reason TEXT,
    expires_at DATETIME,
    created_at DATETIME,
    UNIQUE(user_id, matchable_type, matchable_id)
);
CREATE INDEX IF NOT EXISTS idx_ms_user ON match_scores(user_id);
CREATE INDEX IF NOT EXISTS idx_ms_matchable ON match_scores(matchable_type, matchable_id);

-- ── 7. Recommendations (generated lists) ──
CREATE TABLE IF NOT EXISTS recommendations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255),
    items TEXT NOT NULL,
    model_id INTEGER REFERENCES ml_models(id) ON DELETE SET NULL,
    context TEXT,
    expires_at DATETIME,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_rec_user ON recommendations(user_id);
CREATE INDEX IF NOT EXISTS idx_rec_type ON recommendations(type);

-- ── 8. Recommendation Interactions ──
CREATE TABLE IF NOT EXISTS recommendation_interactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recommendation_id INTEGER NOT NULL REFERENCES recommendations(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    item_type VARCHAR(50) NOT NULL,
    item_id INTEGER NOT NULL,
    interaction_type VARCHAR(30) NOT NULL,
    weight REAL DEFAULT 1.0,
    metadata TEXT,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_ri_recommendation ON recommendation_interactions(recommendation_id);
CREATE INDEX IF NOT EXISTS idx_ri_user ON recommendation_interactions(user_id);
CREATE INDEX IF NOT EXISTS idx_ri_type ON recommendation_interactions(interaction_type);

-- ── 9. A/B Tests ──
CREATE TABLE IF NOT EXISTS a_b_tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    experiment_type VARCHAR(50),
    variants TEXT NOT NULL,
    metrics TEXT,
    sample_size INTEGER,
    status VARCHAR(30) DEFAULT 'draft',
    started_at DATETIME,
    ended_at DATETIME,
    winner_variant VARCHAR(50),
    confidence_level REAL,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 10. A/B Test Assignments ──
CREATE TABLE IF NOT EXISTS a_b_test_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    test_id INTEGER NOT NULL REFERENCES a_b_tests(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    variant VARCHAR(50) NOT NULL,
    created_at DATETIME,
    UNIQUE(test_id, user_id)
);

-- ── 11. Dynamic Pricing Rules ──
CREATE TABLE IF NOT EXISTS dynamic_pricing_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    model_id INTEGER REFERENCES ml_models(id) ON DELETE SET NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INTEGER,
    base_field VARCHAR(50),
    rules_json TEXT NOT NULL,
    min_price REAL,
    max_price REAL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 12. Pipeline Jobs ──
CREATE TABLE IF NOT EXISTS pipeline_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_type VARCHAR(50) NOT NULL,
    name VARCHAR(255),
    config TEXT,
    cron_expression VARCHAR(100),
    priority INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 13. Pipeline Runs ──
CREATE TABLE IF NOT EXISTS pipeline_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pipeline_job_id INTEGER NOT NULL REFERENCES pipeline_jobs(id) ON DELETE CASCADE,
    run_id VARCHAR(50) NOT NULL UNIQUE,
    status VARCHAR(30) DEFAULT 'pending',
    started_at DATETIME,
    completed_at DATETIME,
    error_message TEXT,
    output_summary TEXT,
    metrics TEXT,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_pr_job ON pipeline_runs(pipeline_job_id);
CREATE INDEX IF NOT EXISTS_idx_pr_status ON pipeline_runs(status);

-- ── 14. Pipeline Run Steps ──
CREATE TABLE IF NOT EXISTS pipeline_run_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pipeline_run_id INTEGER NOT NULL REFERENCES pipeline_runs(id) ON DELETE CASCADE,
    step_name VARCHAR(255) NOT NULL,
    step_type VARCHAR(50) NOT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    started_at DATETIME,
    completed_at DATETIME,
    duration_ms INTEGER,
    input_snapshot TEXT,
    output_snapshot TEXT,
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_prs_run ON pipeline_run_steps(pipeline_run_id);

-- ── 15. Feature Flags ──
CREATE TABLE IF NOT EXISTS feature_flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    is_enabled INTEGER DEFAULT 0,
    rollout_percentage INTEGER DEFAULT 100,
    user_segment_ids TEXT,
    metadata TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 16. Agentic Loop Config ──
CREATE TABLE IF NOT EXISTS agentic_loop_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 17. Agentic Loop Cycles ──
CREATE TABLE IF NOT EXISTS agentic_loop_cycles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_number INTEGER NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME,
    observations TEXT,
    decisions TEXT,
    actions_taken TEXT,
    outcomes TEXT,
    status VARCHAR(30) DEFAULT 'running',
    created_at DATETIME
);

-- ── 18. Agentic Loop Logs ──
CREATE TABLE IF NOT EXISTS agentic_loop_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_id INTEGER REFERENCES agentic_loop_cycles(id) ON DELETE CASCADE,
    component VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    input_data TEXT,
    output_data TEXT,
    duration_ms INTEGER,
    status VARCHAR(20) DEFAULT 'success',
    error TEXT,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_all_cycle ON agentic_loop_logs(cycle_id);

-- ── 19. Content Generation Queue ──
CREATE TABLE IF NOT EXISTS content_generation_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    generator_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INTEGER NOT NULL,
    params TEXT,
    status VARCHAR(30) DEFAULT 'queued',
    output_text TEXT,
    output_path VARCHAR(255),
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_cgq_status ON content_generation_queue(status);

-- ── 20. SEO Keyword Tracking ──
CREATE TABLE IF NOT EXISTS seo_keyword_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword VARCHAR(255) NOT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'google',
    ranking_position INTEGER,
    search_volume INTEGER,
    traffic_estimate INTEGER,
    tracked_date DATE NOT NULL,
    page_url VARCHAR(255),
    created_at DATETIME,
    UNIQUE(keyword, source, tracked_date)
);
CREATE INDEX IF NOT EXISTS_idx_skw_keyword ON seo_keyword_tracking(keyword);
CREATE INDEX IF NOT EXISTS_idx_skw_date ON seo_keyword_tracking(tracked_date);

-- =============================================================
-- END OF AI PIPELINE SCHEMA — 20 tables
-- =============================================================
