-- =============================================================
-- KICC E-Commerce Marketplace Schema
-- Products, inventory, orders, shipping, logistics
-- 35 tables • Compatible: SQLite + MySQL/TiDB
-- =============================================================

-- ── 1. Product Categories (hierarchical) ──
CREATE TABLE IF NOT EXISTS product_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    parent_id INTEGER REFERENCES product_categories(id) ON DELETE SET NULL,
    icon VARCHAR(50),
    image_url VARCHAR(255),
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_pc_parent ON product_categories(parent_id);

-- ── 2. Products ──
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    category_id INTEGER REFERENCES product_categories(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    sku VARCHAR(100) NOT NULL UNIQUE,
    barcode VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'piece',
    weight_kg REAL DEFAULT 0,
    length_cm REAL DEFAULT 0,
    width_cm REAL DEFAULT 0,
    height_cm REAL DEFAULT 0,
    is_digital INTEGER DEFAULT 0,
    status VARCHAR(30) DEFAULT 'draft',
    is_featured INTEGER DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    tags TEXT,
    warranty_info TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_products_user ON products(user_id);
CREATE INDEX IF NOT EXISTS idx_products_county ON products(county_id);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_status ON products(status);

-- ── 3. Product Variants ──
CREATE TABLE IF NOT EXISTS product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    price REAL NOT NULL,
    compare_at_price REAL,
    cost_price REAL,
    stock INTEGER DEFAULT 0,
    low_stock_threshold INTEGER DEFAULT 5,
    weight_kg REAL,
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    attributes TEXT,
    image_url VARCHAR(255),
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_pv_product ON product_variants(product_id);

-- ── 4. Product Images ──
CREATE TABLE IF NOT EXISTS product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    variant_id INTEGER REFERENCES product_variants(id) ON DELETE SET NULL,
    url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    alt_text VARCHAR(255),
    sort_order INTEGER DEFAULT 0,
    is_primary INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_pi_product ON product_images(product_id);

-- ── 5. Product Reviews ──
CREATE TABLE IF NOT EXISTS product_reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    variant_id INTEGER REFERENCES product_variants(id) ON DELETE SET NULL,
    order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
    rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    body TEXT,
    pros TEXT,
    cons TEXT,
    is_verified_purchase INTEGER DEFAULT 0,
    is_approved INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(product_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_pr_product ON product_reviews(product_id);
CREATE INDEX IF NOT EXISTS idx_pr_user ON product_reviews(user_id);

-- ── 6. Suppliers ──
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    business_name VARCHAR(255) NOT NULL,
    business_registration VARCHAR(255),
    kra_pin VARCHAR(255),
    tax_status VARCHAR(50),
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(255),
    website VARCHAR(255),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    latitude REAL,
    longitude REAL,
    verification_status VARCHAR(30) DEFAULT 'unverified',
    verified_at DATETIME,
    commission_rate REAL DEFAULT 0.10,
    payment_terms VARCHAR(50) DEFAULT 'net30',
    bank_name VARCHAR(255),
    bank_account_name VARCHAR(255),
    bank_account_number VARCHAR(255),
    bank_code VARCHAR(20),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_suppliers_user ON suppliers(user_id);
CREATE INDEX IF NOT EXISTS idx_suppliers_county ON suppliers(county_id);

-- ── 7. Supplier Products ──
CREATE TABLE IF NOT EXISTS supplier_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    is_primary INTEGER DEFAULT 0,
    commission_rate REAL,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(supplier_id, product_id)
);

-- ── 8. Warehouses ──
CREATE TABLE IF NOT EXISTS warehouses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20),
    latitude REAL,
    longitude REAL,
    contact_name VARCHAR(255),
    contact_phone VARCHAR(20),
    capacity_cubic_m REAL DEFAULT 0,
    capacity_weight_kg REAL DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 9. Inventory ──
CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    warehouse_id INTEGER NOT NULL REFERENCES warehouses(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 0,
    reserved_quantity INTEGER NOT NULL DEFAULT 0,
    available_quantity INTEGER GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
    low_stock_threshold INTEGER DEFAULT 5,
    location_aisle VARCHAR(50),
    location_shelf VARCHAR(50),
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(warehouse_id, variant_id)
);
CREATE INDEX IF NOT EXISTS idx_inv_warehouse ON inventory(warehouse_id);
CREATE INDEX IF NOT EXISTS idx_inv_variant ON inventory(variant_id);

-- ── 10. Inventory Movements ──
CREATE TABLE IF NOT EXISTS inventory_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    inventory_id INTEGER NOT NULL REFERENCES inventory(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    warehouse_id INTEGER NOT NULL REFERENCES warehouses(id) ON DELETE CASCADE,
    quantity_change INTEGER NOT NULL,
    running_quantity INTEGER NOT NULL,
    type VARCHAR(30) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    notes TEXT,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_im_inventory ON inventory_movements(inventory_id);
CREATE INDEX IF NOT EXISTS idx_im_type ON inventory_movements(type);

-- ── 11. Shopping Carts ──
CREATE TABLE IF NOT EXISTS shopping_carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255),
    coupon_code VARCHAR(100),
    discount_amount REAL DEFAULT 0,
    notes TEXT,
    expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_sc_user ON shopping_carts(user_id);

-- ── 12. Cart Items ──
CREATE TABLE IF NOT EXISTS cart_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cart_id INTEGER NOT NULL REFERENCES shopping_carts(id) ON DELETE CASCADE,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price REAL NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(cart_id, variant_id)
);
CREATE INDEX IF NOT EXISTS idx_ci_cart ON cart_items(cart_id);

-- ── 13. Orders ──
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    cart_id INTEGER REFERENCES shopping_carts(id) ON DELETE SET NULL,
    billing_address_id INTEGER REFERENCES order_addresses(id) ON DELETE SET NULL,
    shipping_address_id INTEGER REFERENCES order_addresses(id) ON DELETE SET NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    subtotal REAL NOT NULL DEFAULT 0,
    discount_total REAL DEFAULT 0,
    tax_total REAL DEFAULT 0,
    shipping_total REAL DEFAULT 0,
    grand_total REAL NOT NULL DEFAULT 0,
    paid_total REAL DEFAULT 0,
    payment_status VARCHAR(30) DEFAULT 'pending',
    fulfillment_status VARCHAR(30) DEFAULT 'unfulfilled',
    coupon_code VARCHAR(100),
    notes TEXT,
    staff_notes TEXT,
    is_gift INTEGER DEFAULT 0,
    gift_message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    placed_at DATETIME,
    paid_at DATETIME,
    fulfilled_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_payment ON orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_fulfillment ON orders(fulfillment_status);
CREATE INDEX IF NOT EXISTS idx_orders_placed ON orders(placed_at);

-- ── 14. Order Items ──
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    variant_id INTEGER REFERENCES product_variants(id) ON DELETE SET NULL,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_name VARCHAR(255),
    sku VARCHAR(100),
    unit_price REAL NOT NULL,
    quantity INTEGER NOT NULL,
    discount_total REAL DEFAULT 0,
    tax_total REAL DEFAULT 0,
    total REAL NOT NULL,
    commission_rate REAL,
    commission_amount REAL,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_oi_order ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_oi_product ON order_items(product_id);
CREATE INDEX IF NOT EXISTS idx_oi_supplier ON order_items(supplier_id);

-- ── 15. Order Addresses ──
CREATE TABLE IF NOT EXISTS order_addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) NOT NULL DEFAULT 'Kenya',
    latitude REAL,
    longitude REAL,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_oa_order ON order_addresses(order_id);

-- ── 16. Order Status History ──
CREATE TABLE IF NOT EXISTS order_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    status_from VARCHAR(30),
    status_to VARCHAR(30) NOT NULL,
    notes TEXT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_osh_order ON order_status_history(order_id);

-- ── 17. Shipments ──
CREATE TABLE IF NOT EXISTS shipments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    warehouse_id INTEGER REFERENCES warehouses(id) ON DELETE SET NULL,
    tracking_number VARCHAR(255) NOT NULL UNIQUE,
    courier_name VARCHAR(100) NOT NULL,
    courier_service VARCHAR(100),
    shipping_method VARCHAR(50),
    weight_kg REAL,
    length_cm REAL,
    width_cm REAL,
    height_cm REAL,
    status VARCHAR(30) DEFAULT 'pending',
    estimated_fee REAL,
    actual_fee REAL,
    packaged_at DATETIME,
    picked_up_at DATETIME,
    shipped_at DATETIME,
    estimated_delivery DATETIME,
    delivered_at DATETIME,
    signature VARCHAR(255),
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_shipments_order ON shipments(order_id);
CREATE INDEX IF NOT EXISTS idx_shipments_courier ON shipments(courier_name);

-- ── 18. Shipment Items ──
CREATE TABLE IF NOT EXISTS shipment_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shipment_id INTEGER NOT NULL REFERENCES shipments(id) ON DELETE CASCADE,
    order_item_id INTEGER NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    created_at DATETIME
);

-- ── 19. Shipment Tracking Events ──
CREATE TABLE IF NOT EXISTS shipment_tracking_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shipment_id INTEGER NOT NULL REFERENCES shipments(id) ON DELETE CASCADE,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(255),
    description TEXT,
    occurred_at DATETIME NOT NULL,
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_ste_shipment ON shipment_tracking_events(shipment_id);

-- ── 20. Return Requests ──
CREATE TABLE IF NOT EXISTS return_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_item_id INTEGER REFERENCES order_items(id) ON DELETE SET NULL,
    reason VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    evidence_photos TEXT,
    preferred_action VARCHAR(30) DEFAULT 'refund',
    status VARCHAR(30) DEFAULT 'pending',
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at DATETIME,
    resolution_notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_rr_order ON return_requests(order_id);
CREATE INDEX IF NOT EXISTS idx_rr_user ON return_requests(user_id);

-- ── 21. Wishlists ──
CREATE TABLE IF NOT EXISTS wishlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) DEFAULT 'Default',
    is_public INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_wl_user ON wishlists(user_id);

-- ── 22. Wishlist Items ──
CREATE TABLE IF NOT EXISTS wishlist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wishlist_id INTEGER NOT NULL REFERENCES wishlists(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    variant_id INTEGER REFERENCES product_variants(id) ON DELETE SET NULL,
    notes TEXT,
    created_at DATETIME,
    UNIQUE(wishlist_id, product_id, variant_id)
);

-- ── 23. Coupon Codes ──
CREATE TABLE IF NOT EXISTS coupon_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    type VARCHAR(30) NOT NULL DEFAULT 'percentage',
    value REAL NOT NULL,
    min_order_amount REAL,
    max_discount_amount REAL,
    usage_limit INTEGER DEFAULT 0,
    usage_per_user INTEGER DEFAULT 1,
    used_count INTEGER DEFAULT 0,
    product_ids TEXT,
    category_ids TEXT,
    starts_at DATETIME,
    ends_at DATETIME,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_cc_code ON coupon_codes(code);

-- ── 24. Coupon Usages ──
CREATE TABLE IF NOT EXISTS coupon_usages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coupon_id INTEGER NOT NULL REFERENCES coupon_codes(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
    discount_amount REAL NOT NULL,
    created_at DATETIME
);

-- ── 25. Shipping Zones ──
CREATE TABLE IF NOT EXISTS shipping_zones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    countries TEXT NOT NULL,
    regions TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 26. Shipping Rates ──
CREATE TABLE IF NOT EXISTS shipping_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    zone_id INTEGER NOT NULL REFERENCES shipping_zones(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'flat',
    min_weight_kg REAL DEFAULT 0,
    max_weight_kg REAL,
    min_total REAL,
    max_total REAL,
    rate REAL NOT NULL,
    additional_item_rate REAL DEFAULT 0,
    estimated_days_min INTEGER,
    estimated_days_max INTEGER,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_sr_zone ON shipping_rates(zone_id);

-- ── 27. Courier Partners ──
CREATE TABLE IF NOT EXISTS courier_partners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    api_endpoint VARCHAR(255),
    api_key_encrypted TEXT,
    tracking_url_template VARCHAR(255),
    supported_countries TEXT,
    services TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 28. Pickup Requests ──
CREATE TABLE IF NOT EXISTS pickup_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shipment_id INTEGER NOT NULL REFERENCES shipments(id) ON DELETE CASCADE,
    supplier_id INTEGER NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
    courier_partner_id INTEGER REFERENCES courier_partners(id) ON DELETE SET NULL,
    pickup_address TEXT NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time_window VARCHAR(50),
    special_instructions TEXT,
    status VARCHAR(30) DEFAULT 'requested',
    confirmed_at DATETIME,
    picked_up_at DATETIME,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 29. Product Questions ──
CREATE TABLE IF NOT EXISTS product_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    question TEXT NOT NULL,
    answer TEXT,
    answered_by INTEGER REFERENCES users(id),
    answered_at DATETIME,
    is_published INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_pq_product ON product_questions(product_id);

-- ── 30. Price History ──
CREATE TABLE IF NOT EXISTS price_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    variant_id INTEGER NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
    price REAL NOT NULL,
    compare_at_price REAL,
    cost_price REAL,
    changed_by INTEGER REFERENCES users(id),
    reason VARCHAR(100),
    created_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_ph_variant ON price_history(variant_id);

-- =============================================================
-- END OF COMMERCE SCHEMA — 30 tables
-- =============================================================
