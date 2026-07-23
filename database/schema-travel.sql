-- =============================================================
-- KICC Travel & Tourism Schema
-- Flights, hotels, restaurants, transfers, packages
-- 30 tables • Compatible: SQLite + MySQL/TiDB
-- =============================================================

-- ── 1. Airlines ──
CREATE TABLE IF NOT EXISTS airlines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    iata_code VARCHAR(3) NOT NULL UNIQUE,
    icao_code VARCHAR(4),
    country VARCHAR(100) NOT NULL,
    logo_url VARCHAR(255),
    website VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 2. Airports ──
CREATE TABLE IF NOT EXISTS airports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    iata_code VARCHAR(3) NOT NULL UNIQUE,
    icao_code VARCHAR(4),
    city VARCHAR(100) NOT NULL,
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Kenya',
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    altitude_ft INTEGER,
    timezone VARCHAR(50),
    is_international INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 3. Flights ──
CREATE TABLE IF NOT EXISTS flights (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    airline_id INTEGER NOT NULL REFERENCES airlines(id) ON DELETE CASCADE,
    flight_number VARCHAR(10) NOT NULL,
    origin_airport_id INTEGER NOT NULL REFERENCES airports(id) ON DELETE CASCADE,
    destination_airport_id INTEGER NOT NULL REFERENCES airports(id) ON DELETE CASCADE,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    duration_minutes INTEGER NOT NULL,
    days_of_week VARCHAR(20),
    aircraft_type VARCHAR(50),
    base_price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(20) DEFAULT 'scheduled',
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(airline_id, flight_number)
);
CREATE INDEX IF NOT EXISTS idx_flights_origin ON flights(origin_airport_id);
CREATE INDEX IF NOT EXISTS idx_flights_dest ON flights(destination_airport_id);

-- ── 4. Flight Inventory / Fare Classes ──
CREATE TABLE IF NOT EXISTS flight_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flight_id INTEGER NOT NULL REFERENCES flights(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    fare_class VARCHAR(10) NOT NULL DEFAULT 'economy',
    total_seats INTEGER NOT NULL,
    available_seats INTEGER NOT NULL,
    price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(flight_id, date, fare_class)
);
CREATE INDEX IF NOT EXISTS_idx_fi_flight_date ON flight_inventory(flight_id, date);

-- ── 5. Flight Bookings ──
CREATE TABLE IF NOT EXISTS flight_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    flight_id INTEGER NOT NULL REFERENCES flights(id) ON DELETE CASCADE,
    flight_inventory_id INTEGER REFERENCES flight_inventory(id) ON DELETE SET NULL,
    fare_class VARCHAR(10) DEFAULT 'economy',
    passenger_count INTEGER NOT NULL,
    subtotal REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    pnr_code VARCHAR(20),
    cancellation_policy TEXT,
    booked_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_fb_user ON flight_bookings(user_id);
CREATE INDEX IF NOT EXISTS idx_fb_flight ON flight_bookings(flight_id);

-- ── 6. Flight Passengers ──
CREATE TABLE IF NOT EXISTS flight_passengers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    flight_booking_id INTEGER NOT NULL REFERENCES flight_bookings(id) ON DELETE CASCADE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    title VARCHAR(10),
    nationality VARCHAR(100) DEFAULT 'Kenyan',
    passport_number VARCHAR(50),
    passport_expiry DATE,
    id_number VARCHAR(50),
    date_of_birth DATE,
    seat_number VARCHAR(10),
    baggage_allowance_kg INTEGER DEFAULT 23,
    special_requests TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_fp_booking ON flight_passengers(flight_booking_id);

-- ── 7. Hotels ──
CREATE TABLE IF NOT EXISTS hotels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    star_rating INTEGER CHECK(star_rating >= 1 AND star_rating <= 5),
    address VARCHAR(255) NOT NULL,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    phone VARCHAR(255),
    email VARCHAR(255),
    website VARCHAR(255),
    check_in_time TIME DEFAULT '14:00',
    check_out_time TIME DEFAULT '10:00',
    amenities TEXT,
    policies TEXT,
    images TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_hotels_county ON hotels(county_id);

-- ── 8. Hotel Rooms ──
CREATE TABLE IF NOT EXISTS hotel_rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hotel_id INTEGER NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    description TEXT,
    max_guests INTEGER NOT NULL DEFAULT 2,
    total_rooms INTEGER NOT NULL DEFAULT 1,
    price_per_night REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    size_sq_m REAL,
    bed_configuration VARCHAR(100),
    amenities TEXT,
    images TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_hr_hotel ON hotel_rooms(hotel_id);

-- ── 9. Hotel Room Inventory ──
CREATE TABLE IF NOT EXISTS hotel_room_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hotel_room_id INTEGER NOT NULL REFERENCES hotel_rooms(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    available_rooms INTEGER NOT NULL,
    price_override REAL,
    is_blocked INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(hotel_room_id, date)
);
CREATE INDEX IF NOT EXISTS_idx_hri_room_date ON hotel_room_inventory(hotel_room_id, date);

-- ── 10. Hotel Bookings ──
CREATE TABLE IF NOT EXISTS hotel_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    hotel_id INTEGER NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guest_count INTEGER NOT NULL DEFAULT 1,
    subtotal REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    special_requests TEXT,
    cancellation_policy TEXT,
    checked_in_at DATETIME,
    checked_out_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_hb_user ON hotel_bookings(user_id);
CREATE INDEX IF NOT EXISTS idx_hb_hotel ON hotel_bookings(hotel_id);

-- ── 11. Hotel Room Bookings (pivot) ──
CREATE TABLE IF NOT EXISTS hotel_room_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hotel_booking_id INTEGER NOT NULL REFERENCES hotel_bookings(id) ON DELETE CASCADE,
    hotel_room_id INTEGER NOT NULL REFERENCES hotel_rooms(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 1,
    price_per_night REAL NOT NULL,
    total_price REAL NOT NULL,
    created_at DATETIME,
    UNIQUE(hotel_booking_id, hotel_room_id)
);

-- ── 12. Restaurants ──
CREATE TABLE IF NOT EXISTS restaurants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    cuisine_type VARCHAR(100),
    price_range VARCHAR(10),
    address VARCHAR(255) NOT NULL,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    phone VARCHAR(255),
    email VARCHAR(255),
    website VARCHAR(255),
    opening_hours TEXT,
    amenities TEXT,
    images TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_restaurants_county ON restaurants(county_id);

-- ── 13. Restaurant Tables ──
CREATE TABLE IF NOT EXISTS restaurant_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    table_number VARCHAR(20) NOT NULL,
    capacity INTEGER NOT NULL,
    location_hint VARCHAR(100),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(restaurant_id, table_number)
);

-- ── 14. Restaurant Menu Items ──
CREATE TABLE IF NOT EXISTS restaurant_menu_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    is_available INTEGER DEFAULT 1,
    dietary_info TEXT,
    image_url VARCHAR(255),
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_mi_restaurant ON restaurant_menu_items(restaurant_id);

-- ── 15. Restaurant Bookings ──
CREATE TABLE IF NOT EXISTS restaurant_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    table_id INTEGER REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    guest_count INTEGER NOT NULL,
    special_requests TEXT,
    status VARCHAR(30) DEFAULT 'pending',
    arrived_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_rb_user ON restaurant_bookings(user_id);
CREATE INDEX IF NOT EXISTS_idx_rb_restaurant ON restaurant_bookings(restaurant_id);

-- ── 16. Airport Transfers ──
CREATE TABLE IF NOT EXISTS airport_transfers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    airport_id INTEGER NOT NULL REFERENCES airports(id) ON DELETE CASCADE,
    provider_name VARCHAR(255) NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    capacity INTEGER NOT NULL,
    price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 17. Transfer Bookings ──
CREATE TABLE IF NOT EXISTS transfer_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    transfer_id INTEGER NOT NULL REFERENCES airport_transfers(id) ON DELETE CASCADE,
    flight_booking_id INTEGER REFERENCES flight_bookings(id) ON DELETE SET NULL,
    pickup_location VARCHAR(255) NOT NULL,
    dropoff_location VARCHAR(255) NOT NULL,
    pickup_datetime DATETIME NOT NULL,
    flight_number VARCHAR(10),
    passenger_count INTEGER NOT NULL DEFAULT 1,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    driver_name VARCHAR(255),
    driver_phone VARCHAR(20),
    driver_photo VARCHAR(255),
    vehicle_registration VARCHAR(50),
    completed_at DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_tb_user ON transfer_bookings(user_id);

-- ── 18. Travel Packages ──
CREATE TABLE IF NOT EXISTS travel_packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    duration_days INTEGER NOT NULL,
    highlights TEXT,
    itinerary TEXT,
    includes TEXT,
    excludes TEXT,
    base_price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    max_participants INTEGER,
    difficulty_level VARCHAR(30) DEFAULT 'easy',
    images TEXT,
    is_featured INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_tp_county ON travel_packages(county_id);

-- ── 19. Travel Package Items ──
CREATE TABLE IF NOT EXISTS travel_package_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    travel_package_id INTEGER NOT NULL REFERENCES travel_packages(id) ON DELETE CASCADE,
    item_type VARCHAR(30) NOT NULL,
    item_id INTEGER NOT NULL,
    day_number INTEGER,
    sort_order INTEGER DEFAULT 0,
    notes TEXT,
    created_at DATETIME
);

-- ── 20. Travel Package Bookings ──
CREATE TABLE IF NOT EXISTS travel_package_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    travel_package_id INTEGER NOT NULL REFERENCES travel_packages(id) ON DELETE CASCADE,
    start_date DATE NOT NULL,
    participant_count INTEGER NOT NULL DEFAULT 1,
    subtotal REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_tpb_user ON travel_package_bookings(user_id);

-- ── 21. Attractions ──
CREATE TABLE IF NOT EXISTS attractions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER NOT NULL REFERENCES counties(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    address VARCHAR(255),
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    entry_fee REAL,
    currency VARCHAR(3) DEFAULT 'KES',
    opening_hours TEXT,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    website VARCHAR(255),
    images TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_attractions_county ON attractions(county_id);

-- ── 22. Attraction Tickets ──
CREATE TABLE IF NOT EXISTS attraction_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attraction_id INTEGER NOT NULL REFERENCES attractions(id) ON DELETE CASCADE,
    ticket_type VARCHAR(50) NOT NULL,
    price REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 23. Attraction Bookings ──
CREATE TABLE IF NOT EXISTS attraction_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    attraction_id INTEGER NOT NULL REFERENCES attractions(id) ON DELETE CASCADE,
    visit_date DATE NOT NULL,
    ticket_count INTEGER NOT NULL,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 24. Traveler Profiles ──
CREATE TABLE IF NOT EXISTS traveler_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    nationality VARCHAR(100) DEFAULT 'Kenyan',
    language_preference VARCHAR(10) DEFAULT 'en',
    dietary_restrictions TEXT,
    accessibility_needs TEXT,
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relation VARCHAR(50),
    frequent_flyer_numbers TEXT,
    accommodation_preferences TEXT,
    interests TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE(user_id)
);

-- ── 25. Travel Itineraries (generated) ──
CREATE TABLE IF NOT EXISTS travel_itineraries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(30) DEFAULT 'draft',
    notes TEXT,
    is_public INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_ti_user ON travel_itineraries(user_id);

-- ── 26. Travel Itinerary Items ──
CREATE TABLE IF NOT EXISTS travel_itinerary_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    travel_itinerary_id INTEGER NOT NULL REFERENCES travel_itineraries(id) ON DELETE CASCADE,
    day_number INTEGER NOT NULL,
    item_type VARCHAR(30) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INTEGER,
    start_time TIME,
    end_time TIME,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    latitude REAL,
    longitude REAL,
    cost REAL,
    booking_reference VARCHAR(50),
    notes TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS_idx_tii_itinerary ON travel_itinerary_items(travel_itinerary_id);

-- ── 27. Car Rentals ──
CREATE TABLE IF NOT EXISTS car_rentals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    county_id INTEGER REFERENCES counties(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    make VARCHAR(100),
    model VARCHAR(100),
    year INTEGER,
    capacity INTEGER NOT NULL,
    transmission VARCHAR(20),
    price_per_day REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    location VARCHAR(255),
    latitude REAL,
    longitude REAL,
    images TEXT,
    is_available INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

-- ── 28. Car Rental Bookings ──
CREATE TABLE IF NOT EXISTS car_rental_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference VARCHAR(50) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    car_rental_id INTEGER NOT NULL REFERENCES car_rentals(id) ON DELETE CASCADE,
    pickup_location VARCHAR(255) NOT NULL,
    dropoff_location VARCHAR(255),
    pickup_datetime DATETIME NOT NULL,
    dropoff_datetime DATETIME NOT NULL,
    total_days INTEGER NOT NULL,
    total REAL NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    status VARCHAR(30) DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME
);

-- =============================================================
-- END OF TRAVEL SCHEMA — 28 tables
-- =============================================================
