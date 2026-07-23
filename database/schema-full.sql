-- =============================================================
-- KICC DIGITAL ECONOMY PLATFORM — COMPLETE DATABASE SCHEMA
-- =============================================================
-- Total: ~150 tables across 8 domains
-- Compatible: SQLite (local dev), MySQL/TiDB (production)
-- =============================================================
-- This file loads all domain schemas in dependency order.
-- Run: sqlite3 kicc.sqlite < database/schema-full.sql
-- =============================================================

-- ── 1. CORE PLATFORM (56 tables) ──
.read database/schema.sql

-- ── 2. E-COMMERCE MARKETPLACE (30 tables) ──
.read database/schema-commerce.sql

-- ── 3. TRAVEL & TOURISM (28 tables) ──
.read database/schema-travel.sql

-- ── 4. AI PIPELINE (20 tables) ──
.read database/schema-ai-pipeline.sql

-- ── 5. ADVERTISING (15 tables) ──
.read database/schema-advertising.sql

-- ── 6. PAYMENTS & SUBSCRIPTIONS EXPANDED (16 tables) ──
.read database/schema-payments.sql

-- ── 7. SEO & CONTENT (13 tables) ──
.read database/schema-seo-content.sql

-- =============================================================
-- TOTAL: 56 + 30 + 28 + 20 + 15 + 16 + 13 = ~178 tables
-- =============================================================
-- INDEXES: 100+ covering all frequent query patterns
-- FOREIGN KEYS: Full referential integrity with cascading deletes
-- POLYMORPHISM: Used for embeddings, SEO metadata, tags, media
-- GENERATED COLUMNS: available_quantity in inventory
-- =============================================================
