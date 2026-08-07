-- =============================================================
-- KICC National Exhibition Platform — Complete Database Schema
-- Compatible with: SQLite (local dev), MySQL/Aurora (production)
-- 56 tables • Created: 2026-07-23
-- =============================================================

-- ── 1. Users ──
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at DATETIME,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    google_id VARCHAR(255) UNIQUE,
    avatar VARCHAR(255),
    account_type VARCHAR(255),
    phone VARCHAR(255),
    county_id INTEGER,
    id_number VARCHAR(255),
    kra_pin VARCHAR(255),
    business_reg VARCHAR(255),
    phone_verified_at DATETIME,
    mfa_enabled INTEGER DEFAULT 0,
    mfa_secret TEXT,
    status VARCHAR(255) DEFAULT 'active',
    metadata TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 2. Password Reset Tokens ──
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME
);

-- ── 3. Sessions ──
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INTEGER,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);

-- ── 4. Cache ──
CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_cache_expiration ON cache(expiration);

-- ── 5. Cache Locks ──
CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

-- ── 6. Jobs ──
CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_jobs_queue ON jobs(queue);

-- ── 7. Job Batches ──
CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INTEGER NOT NULL,
    pending_jobs INTEGER NOT NULL,
    failed_jobs INTEGER NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT,
    cancelled_at INTEGER,
    created_at INTEGER NOT NULL,
    finished_at INTEGER
);

-- ── 8. Failed Jobs ──
CREATE TABLE IF NOT EXISTS failed_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── 9. Counties ──
CREATE TABLE IF NOT EXISTS counties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    capital VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE,
    former_province VARCHAR(50) NOT NULL,
    economic_zone VARCHAR(50) NOT NULL,
    population_2024 INTEGER,
    area_km2 REAL,
    latitude REAL,
    longitude REAL,
    map_x REAL,
    map_y REAL,
    map_z REAL,
    scene_type VARCHAR(30) DEFAULT 'coast',
    region VARCHAR(50),
    weather_station_id VARCHAR(50),
    primary_sectors TEXT,
    icon_emoji VARCHAR(10),
    profile_image VARCHAR(255),
    tagline VARCHAR(200),
    description TEXT,
    tourism_highlights TEXT,
    warmest_month VARCHAR(50),
    coolest_month VARCHAR(50),
    rainy_season VARCHAR(100),
    dry_season VARCHAR(100),
    slug VARCHAR(100) NOT NULL UNIQUE,
    weather_tags TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 10. Sectors ──
CREATE TABLE IF NOT EXISTS sectors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    emoji VARCHAR(10),
    description TEXT,
    parent_id INTEGER,
    icon VARCHAR(50),
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_sectors_parent ON sectors(parent_id);

-- ── 11. County-Sector Pivot ──
CREATE TABLE IF NOT EXISTS county_sector (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    sector_id INTEGER NOT NULL REFERENCES sectors(id) ON DELETE CASCADE,
    sub_sectors VARCHAR(500),
    UNIQUE(county_id, sector_id)
);

-- ── 12. Seasonal Calendars ──
CREATE TABLE IF NOT EXISTS seasonal_calendars (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    month INTEGER NOT NULL,
    avg_temp_c REAL,
    rainfall_mm REAL,
    tourism_season VARCHAR(20),
    agri_season VARCHAR(30),
    weather_tag VARCHAR(30),
    UNIQUE(county_id, month)
);

-- ── 13. Exhibitions ──
CREATE TABLE IF NOT EXISTS exhibitions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    tagline VARCHAR(255),
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    venue_id INTEGER REFERENCES venues(id) ON DELETE SET NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    open_time TIME,
    close_time TIME,
    cover_image VARCHAR(255),
    gallery TEXT,
    status VARCHAR(255) DEFAULT 'draft',
    is_featured INTEGER DEFAULT 0,
    organizer_info TEXT,
    meta TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);

-- ── 14. Venues ──
CREATE TABLE IF NOT EXISTS venues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    venue_type VARCHAR(255) DEFAULT 'hall',
    address VARCHAR(255),
    city VARCHAR(255),
    county VARCHAR(255),
    latitude REAL,
    longitude REAL,
    capacity INTEGER,
    amenities TEXT,
    contact_info TEXT,
    cover_image VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);

-- ── 15. Event Sessions ──
CREATE TABLE IF NOT EXISTS event_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exhibition_id INTEGER NOT NULL REFERENCES exhibitions(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    description TEXT,
    session_type VARCHAR(255) DEFAULT 'talk',
    speaker VARCHAR(255),
    speaker_title VARCHAR(255),
    speaker_photo VARCHAR(255),
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    location VARCHAR(255),
    max_attendees INTEGER,
    cover_image VARCHAR(255),
    status VARCHAR(255) DEFAULT 'scheduled',
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);

-- ── 16. Booths ──
CREATE TABLE IF NOT EXISTS booths (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exhibition_id INTEGER NOT NULL REFERENCES exhibitions(id) ON DELETE CASCADE,
    booth_number VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    size VARCHAR(255) DEFAULT 'standard',
    category VARCHAR(255) DEFAULT 'standard',
    description TEXT,
    amenities TEXT,
    price REAL NOT NULL,
    discount_price REAL,
    max_quantity INTEGER DEFAULT 1,
    booked_quantity INTEGER DEFAULT 0,
    location_hint VARCHAR(255),
    dimensions TEXT,
    images TEXT,
    status VARCHAR(255) DEFAULT 'available',
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME,
    UNIQUE(exhibition_id, booth_number)
);

-- ── 17. Bookings ──
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(255) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    exhibition_id INTEGER NOT NULL REFERENCES exhibitions(id) ON DELETE CASCADE,
    booking_type VARCHAR(255) DEFAULT 'booth',
    subtotal REAL DEFAULT 0,
    tax REAL DEFAULT 0,
    total REAL DEFAULT 0,
    currency VARCHAR(255) DEFAULT 'KES',
    status VARCHAR(255) DEFAULT 'pending',
    billing_info TEXT,
    notes TEXT,
    paid_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);

-- ── 18. Booking Booths ──
CREATE TABLE IF NOT EXISTS booking_booths (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    booth_id INTEGER NOT NULL REFERENCES booths(id) ON DELETE CASCADE,
    price REAL NOT NULL,
    discount REAL DEFAULT 0,
    exhibitor_name VARCHAR(255),
    exhibitor_email VARCHAR(255),
    exhibitor_phone VARCHAR(255),
    requirements TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(booking_id, booth_id)
);

-- ── 19. Ticket Types ──
CREATE TABLE IF NOT EXISTS ticket_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exhibition_id INTEGER NOT NULL REFERENCES exhibitions(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    description TEXT,
    price REAL NOT NULL,
    discount_price REAL,
    currency VARCHAR(255) DEFAULT 'KES',
    quantity INTEGER DEFAULT 0,
    sold INTEGER DEFAULT 0,
    max_per_order INTEGER DEFAULT 10,
    sale_start DATETIME,
    sale_end DATETIME,
    benefits TEXT,
    color VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);

-- ── 20. Tickets ──
CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_code VARCHAR(255) NOT NULL UNIQUE,
    booking_id INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    ticket_type_id INTEGER NOT NULL REFERENCES ticket_types(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    price REAL NOT NULL,
    status VARCHAR(255) DEFAULT 'active',
    qr_code VARCHAR(255),
    holder_name VARCHAR(255),
    holder_email VARCHAR(255),
    check_in_data TEXT,
    checked_in_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);

-- ── 21. Phone Verification Codes ──
CREATE TABLE IF NOT EXISTS phone_verification_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone VARCHAR(20) NOT NULL,
    code VARCHAR(6) NOT NULL,
    purpose VARCHAR(255) DEFAULT 'registration',
    expires_at DATETIME NOT NULL,
    used_at DATETIME,
    attempts INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_phone_codes ON phone_verification_codes(phone, code, purpose);

-- ── 22. Subscription Plans ──
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    billing_interval VARCHAR(20) DEFAULT 'monthly',
    features TEXT,
    max_booths INTEGER DEFAULT 0,
    max_exhibitions INTEGER DEFAULT 0,
    max_media_files INTEGER DEFAULT 0,
    has_livestream INTEGER DEFAULT 0,
    has_analytics INTEGER DEFAULT 0,
    has_priority_support INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 23. User Subscriptions ──
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    subscription_plan_id INTEGER NOT NULL REFERENCES subscription_plans(id) ON DELETE CASCADE,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME,
    trial_ends_at DATETIME,
    status VARCHAR(20) DEFAULT 'active',
    payment_provider VARCHAR(255),
    payment_provider_id VARCHAR(255),
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 24. Payment Methods ──
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider VARCHAR(50) NOT NULL,
    provider_id VARCHAR(255),
    type VARCHAR(50) NOT NULL,
    last_four VARCHAR(4),
    phone_number VARCHAR(255),
    is_default INTEGER DEFAULT 0,
    expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 25. Payment Transactions ──
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    transaction_id VARCHAR(255) NOT NULL UNIQUE,
    provider VARCHAR(50) NOT NULL,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    reference_type VARCHAR(255),
    reference_id INTEGER,
    metadata TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 26. Escrow Transactions ──
CREATE TABLE IF NOT EXISTS escrow_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    buyer_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    seller_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    escrow_id VARCHAR(255) NOT NULL UNIQUE,
    amount REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    reference_type VARCHAR(255),
    reference_id INTEGER,
    steps TEXT,
    current_step INTEGER DEFAULT 0,
    buyer_confirmed_at DATETIME,
    seller_confirmed_at DATETIME,
    delivery_confirmed_at DATETIME,
    released_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 27. Dispute Cases ──
CREATE TABLE IF NOT EXISTS dispute_cases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    escrow_transaction_id INTEGER NOT NULL REFERENCES escrow_transactions(id) ON DELETE CASCADE,
    raised_by INTEGER NOT NULL REFERENCES users(id),
    reason VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(30) DEFAULT 'open',
    resolution VARCHAR(255),
    resolved_at DATETIME,
    resolved_by INTEGER REFERENCES users(id),
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 28. Courier Shipments ──
CREATE TABLE IF NOT EXISTS courier_shipments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    escrow_transaction_id INTEGER NOT NULL REFERENCES escrow_transactions(id) ON DELETE CASCADE,
    tracking_number VARCHAR(255) NOT NULL UNIQUE,
    courier_name VARCHAR(255) NOT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    origin_address TEXT NOT NULL,
    destination_address TEXT NOT NULL,
    shipped_at DATETIME,
    estimated_delivery DATETIME,
    delivered_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 29. Courier Tracking Events ──
CREATE TABLE IF NOT EXISTS courier_tracking_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    courier_shipment_id INTEGER NOT NULL REFERENCES courier_shipments(id) ON DELETE CASCADE,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(255),
    description TEXT,
    occurred_at DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 30. Sector Entities ──
CREATE TABLE IF NOT EXISTS sector_entities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    sector_id INTEGER NOT NULL REFERENCES sectors(id) ON DELETE CASCADE,
    entity_type VARCHAR(255) NOT NULL,
    entity_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sector_type VARCHAR(50) NOT NULL,
    capture_status VARCHAR(10) DEFAULT 'none',
    sponsor_funder_tag VARCHAR(255),
    latitude REAL,
    longitude REAL,
    contact_info TEXT,
    social_links TEXT,
    is_published INTEGER DEFAULT 0,
    language_primary VARCHAR(5) DEFAULT 'en',
    tags TEXT,
    verification_owner VARCHAR(255),
    verification_date DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_sector_entities_county_sector ON sector_entities(county_id, sector_id);

-- ── 31. Sector Entity Reviews ──
CREATE TABLE IF NOT EXISTS sector_entity_reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sector_entity_id INTEGER NOT NULL REFERENCES sector_entities(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    review TEXT,
    is_verified_purchase INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 32. Entity Trust Scores ──
CREATE TABLE IF NOT EXISTS entity_trust_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sector_entity_id INTEGER NOT NULL REFERENCES sector_entities(id) ON DELETE CASCADE,
    trust_score INTEGER DEFAULT 0,
    visibility_score INTEGER DEFAULT 0,
    trust_grade CHAR(1),
    is_sponsored INTEGER DEFAULT 0,
    signal_breakdown TEXT,
    calculated_at DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 33. Entity Media ──
CREATE TABLE IF NOT EXISTS entity_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mediable_type VARCHAR(255) NOT NULL,
    mediable_id INTEGER NOT NULL,
    type VARCHAR(30) NOT NULL,
    url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    alt_text VARCHAR(255),
    sort_order INTEGER DEFAULT 0,
    metadata TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_entity_media_mediable ON entity_media(mediable_type, mediable_id);

-- ── 34. Immersive Content Records ──
CREATE TABLE IF NOT EXISTS immersive_content_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sector_entity_id INTEGER REFERENCES sector_entities(id) ON DELETE SET NULL,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    tier VARCHAR(10) NOT NULL,
    content_type VARCHAR(30) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    preview_url VARCHAR(255),
    metadata TEXT,
    is_published INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 35. County Tourism Attractions ──
CREATE TABLE IF NOT EXISTS county_tourism_attractions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    location VARCHAR(255),
    entry_fee REAL,
    opening_hours VARCHAR(255),
    contact VARCHAR(255),
    latitude REAL,
    longitude REAL,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 36. County Hotels ──
CREATE TABLE IF NOT EXISTS county_hotels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    star_rating INTEGER,
    description TEXT,
    location VARCHAR(255),
    phone VARCHAR(255),
    email VARCHAR(255),
    website VARCHAR(255),
    latitude REAL,
    longitude REAL,
    price_range_min REAL,
    price_range_max REAL,
    amenities TEXT,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 37. County Products ──
CREATE TABLE IF NOT EXISTS county_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    price REAL,
    unit VARCHAR(255),
    status VARCHAR(20) DEFAULT 'available',
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 38. County Institutions ──
CREATE TABLE IF NOT EXISTS county_institutions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    phone VARCHAR(255),
    email VARCHAR(255),
    website VARCHAR(255),
    student_count INTEGER,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 39. County Farms ──
CREATE TABLE IF NOT EXISTS county_farms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    contact VARCHAR(255),
    size_acres REAL,
    main_crops VARCHAR(255),
    products VARCHAR(255),
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 40. County Transport ──
CREATE TABLE IF NOT EXISTS county_transport (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    operator VARCHAR(255),
    contact VARCHAR(255),
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 41. County Health Facilities ──
CREATE TABLE IF NOT EXISTS county_health_facilities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    level VARCHAR(30) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    phone VARCHAR(255),
    email VARCHAR(255),
    services VARCHAR(255),
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 42. County Culture Sites ──
CREATE TABLE IF NOT EXISTS county_culture_sites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    community VARCHAR(255),
    contact VARCHAR(255),
    latitude REAL,
    longitude REAL,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 43. County-User Pivot ──
CREATE TABLE IF NOT EXISTS county_user (
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) DEFAULT 'admin',
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY(county_id, user_id)
);

-- ── 44-48. Spatie Permissions ──
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(name, guard_name)
);

CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(name, guard_name)
);

CREATE TABLE IF NOT EXISTS model_has_permissions (
    permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id INTEGER NOT NULL,
    PRIMARY KEY(permission_id, model_id, model_type)
);

CREATE TABLE IF NOT EXISTS model_has_roles (
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id INTEGER NOT NULL,
    PRIMARY KEY(role_id, model_id, model_type)
);

CREATE TABLE IF NOT EXISTS role_has_permissions (
    permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY(permission_id, role_id)
);

-- ── 49. User MFA Devices ──
CREATE TABLE IF NOT EXISTS user_mfa_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(30) NOT NULL,
    secret VARCHAR(255),
    phone_number VARCHAR(255),
    email VARCHAR(255),
    last_used_at DATETIME,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 50. OAuth Clients ──
CREATE TABLE IF NOT EXISTS oauth_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    client_id VARCHAR(80) NOT NULL UNIQUE,
    client_secret VARCHAR(80) NOT NULL,
    redirect_uri TEXT NOT NULL,
    allowed_scopes TEXT,
    is_confidential INTEGER DEFAULT 1,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 51. OAuth Tokens ──
CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    oauth_client_id INTEGER NOT NULL REFERENCES oauth_clients(id) ON DELETE CASCADE,
    access_token VARCHAR(255) NOT NULL UNIQUE,
    refresh_token VARCHAR(255) UNIQUE,
    scopes TEXT,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 52. Audit Logs ──
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INTEGER,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_audit_logs_resource ON audit_logs(resource_type, resource_id);

-- ── 53. Notification Logs ──
CREATE TABLE IF NOT EXISTS notification_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    channel VARCHAR(30) NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'sent',
    provider_reference VARCHAR(255),
    sent_at DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 54. Advertisements ──
CREATE TABLE IF NOT EXISTS advertisements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(30) NOT NULL,
    placement VARCHAR(50) NOT NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    target_url VARCHAR(255),
    image_url VARCHAR(255),
    budget REAL,
    spent REAL DEFAULT 0,
    starts_at DATETIME,
    ends_at DATETIME,
    is_active INTEGER DEFAULT 1,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 55. Screen Images ──
CREATE TABLE IF NOT EXISTS screen_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename VARCHAR(255) NOT NULL,
    storage_path TEXT NOT NULL,
    original_path TEXT NOT NULL,
    county_id VARCHAR(50),
    sector_ids VARCHAR(255) DEFAULT '',
    tags VARCHAR(255) DEFAULT '',
    quality_score REAL DEFAULT 0.5,
    scene_type VARCHAR(50) DEFAULT 'unknown',
    width INTEGER DEFAULT 0,
    height INTEGER DEFAULT 0,
    brightness REAL DEFAULT 0.5,
    contrast REAL DEFAULT 0.3,
    has_water INTEGER DEFAULT 0,
    source VARCHAR(50) DEFAULT 'upload',
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_si_county ON screen_images(county_id);
CREATE INDEX IF NOT EXISTS idx_si_sector ON screen_images(sector_ids);
CREATE INDEX IF NOT EXISTS idx_si_scene ON screen_images(scene_type);

-- ── 56. Screens ──
CREATE TABLE IF NOT EXISTS screens (
    id VARCHAR(50) PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    location VARCHAR(100) DEFAULT '',
    county_id VARCHAR(50),
    sector_id VARCHAR(50),
    target_duration_sec INTEGER DEFAULT 60,
    min_images INTEGER DEFAULT 10,
    max_images INTEGER DEFAULT 30,
    refresh_interval_min INTEGER DEFAULT 60,
    active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_screens_county ON screens(county_id);
CREATE INDEX IF NOT EXISTS idx_screens_sector ON screens(sector_id);

-- =============================================================
-- END OF SCHEMA — 56 tables
-- =============================================================
