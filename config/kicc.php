<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Display Priority Algorithm
    |--------------------------------------------------------------------------
    | Weights for the review-driven recommendation scoring.
    */
    'display_priority' => [
        'sector_boost' => 0.08,
        'completeness_boost' => 0.02,
        'freshness_boost' => 0.01,
        'freshness_days' => 14,
        'synced_threshold' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Correlation Engine
    |--------------------------------------------------------------------------
    | Weights for proximity + diversity scoring.
    */
    'correlation' => [
        'geo_weight' => 0.25,
        'diversity_weight' => 0.35,
        'review_weight' => 0.20,
        'completeness_weight' => 0.10,
        'sector_weight' => 0.10,
        'same_type_penalty' => 0.85,
        'affinity_boost' => 1.1,
        'max_places_to_visit' => 4,
        'max_places_to_stay' => 3,
        'max_transport' => 4,
        'completeness_description' => 0.35,
        'completeness_story' => 0.20,
        'completeness_media' => 0.20,
        'completeness_coords' => 0.15,
        'completeness_website' => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Experience Type Affinity Matrix
    |--------------------------------------------------------------------------
    | Defines which experience types complement each other for diversity.
    */
    'experience_types' => [
        'culture' => ['label' => 'Culture & Heritage', 'emoji' => '🏛'],
        'food' => ['label' => 'Food & Dining', 'emoji' => '🍽'],
        'nature' => ['label' => 'Nature & Outdoors', 'emoji' => '🌿'],
        'hotel' => ['label' => 'Places to Stay', 'emoji' => '🏨'],
        'shopping' => ['label' => 'Shopping & Markets', 'emoji' => '🛍'],
        'entertainment' => ['label' => 'Entertainment', 'emoji' => '🎬'],
        'transport' => ['label' => 'Transport', 'emoji' => '🚗'],
        'education' => ['label' => 'Education', 'emoji' => '📚'],
        'industry' => ['label' => 'Industry & Trade', 'emoji' => '🏭'],
        'wellness' => ['label' => 'Wellness & Spa', 'emoji' => '💆'],
    ],
    'type_affinity' => [
        'culture' => ['food', 'nature', 'shopping', 'entertainment', 'hotel', 'transport'],
        'food' => ['culture', 'nature', 'entertainment', 'hotel', 'transport'],
        'nature' => ['food', 'culture', 'entertainment', 'hotel', 'transport'],
        'hotel' => ['food', 'entertainment', 'nature', 'culture', 'transport', 'shopping'],
        'shopping' => ['food', 'culture', 'entertainment', 'hotel', 'transport'],
        'entertainment' => ['food', 'nature', 'culture', 'hotel', 'transport'],
        'transport' => ['hotel', 'food', 'culture', 'nature', 'entertainment'],
        'education' => ['nature', 'culture', 'food', 'hotel', 'transport'],
        'industry' => ['food', 'hotel', 'transport', 'culture'],
        'wellness' => ['nature', 'hotel', 'food', 'culture', 'transport'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Institution Type Classification
    |--------------------------------------------------------------------------
    | Maps institution type strings to experience categories.
    */
    'institution_type_map' => [
        'National Monument' => 'culture',
        'Heritage Site' => 'culture',
        'Museum' => 'culture',
        'Historic Site' => 'culture',
        'Marina' => 'entertainment',
        'Beach Resort' => 'hotel',
        'Hotel' => 'hotel',
        'Restaurant' => 'food',
        'Nature Sanctuary' => 'nature',
        'Nature Trail' => 'nature',
        'Park' => 'nature',
        'Workshop' => 'shopping',
        'Cooperative' => 'shopping',
        'Market' => 'shopping',
        'Airport' => 'transport',
        'Terminal' => 'transport',
        'Port' => 'transport',
        'Bypass' => 'transport',
        'University' => 'education',
        'Institute' => 'education',
        'School' => 'education',
        'College' => 'education',
        'Hospital' => 'wellness',
        'Golf Club' => 'entertainment',
        'Convention Centre' => 'entertainment',
        'Water Park' => 'entertainment',
        'Chamber of Commerce' => 'industry',
        'Manufacturers Association' => 'industry',
        'Manufacturing' => 'industry',
        'Cement' => 'industry',
        'Oil' => 'industry',
        'Special Economic Zone' => 'industry',
        'Port Authority' => 'transport',
        'Maritime' => 'transport',
        'Shipyard' => 'industry',
        'SGR Terminus' => 'transport',
        'Inland Container Depot' => 'transport',
        'Showground' => 'entertainment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Vendor Scoring
    |--------------------------------------------------------------------------
    | Weights and thresholds for the two-score vendor grading.
    */
    'vendor_scoring' => [
        'verification_email' => 10,
        'verification_phone' => 10,
        'verification_kra_pin' => 15,
        'verification_id_number' => 5,
        'fulfillment_max' => 30,
        'fulfillment_neutral' => 15,
        'dispute_penalty_per' => 5,
        'dispute_penalty_max' => 20,
        'breadth_cap' => 10,
        'age_score_per_month' => 1,
        'age_score_max' => 10,
        'grade_a' => 80,
        'grade_b' => 60,
        'grade_c' => 40,
        'visibility_subscription' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Destination Matching
    |--------------------------------------------------------------------------
    | Weather-based destination recommendation scoring.
    */
    'destination_matching' => [
        'base_score' => 50,
        'temp_bonus_high' => 35,
        'temp_bonus_medium' => 10,
        'tourism_bonus' => 15,
        'default_bonus' => 5,
        'max_score' => 100,
        'cold_threshold' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Experience Pricing
    |--------------------------------------------------------------------------
    | Season multipliers, distance tiers, rating formula.
    */
    'experience_pricing' => [
        'peak_season_start_jul' => 182,
        'peak_season_end_aug' => 243,
        'peak_season_start_dec' => 349,
        'peak_season_end_jan' => 15,
        'peak_multiplier' => 1.15,
        'off_peak_start_mar' => 60,
        'off_peak_end_may' => 151,
        'off_peak_start_oct' => 274,
        'off_peak_end_nov' => 334,
        'off_peak_multiplier' => 0.90,
        'rating_formula_min' => 0.6,
        'rating_formula_range' => 0.6,
        'rating_floor' => 0.9,
        'distance_tier1_km' => 10,
        'distance_tier1_mult' => 1.0,
        'distance_tier2_km' => 50,
        'distance_tier2_mult' => 1.1,
        'distance_tier3_km' => 100,
        'distance_tier3_mult' => 1.2,
        'distance_tier4_km' => 300,
        'distance_tier4_mult' => 1.3,
        'distance_tier5_km' => 500,
        'distance_tier5_mult' => 1.5,
        'distance_max_mult' => 2.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing & Tax
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'vat_rate' => 16,
        'default_tax' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Escrow & Disputes
    |--------------------------------------------------------------------------
    */
    'escrow' => [
        'auto_resolve_threshold' => 1000,
        'auto_review_threshold' => 5000,
        'trust_grades_auto' => ['A', 'B'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Packages
    |--------------------------------------------------------------------------
    */
    'subscription_packages' => [
        'county_basic' => 10000,
        'county_premium' => 50000,
        'county_enterprise' => 200000,
        'county_corporate' => 500000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport Integration
    |--------------------------------------------------------------------------
    */
    'transport' => [
        'category_slugs' => ['transport', 'private-transport', 'airport-transfer', 'car-hire', 'taxi', 'dhow-cruise', 'boat', 'travel', 'logistics'],
        'hub_types' => ['Airport', 'Port', 'Terminal', 'Bypass', 'SGR', 'Inland Container Depot', 'Maritime Authority'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Screen & Sector Media
    |--------------------------------------------------------------------------
    | Maps sector slugs to media key suffixes for immersive videos.
    */
    'sector_media_map' => [
        'tourism' => 'tourism',
        'agriculture' => 'farms',
        'fisheries' => 'farms',
        'health' => 'health',
        'education' => 'institutions',
        'culture' => 'culture',
        'creative' => 'culture',
        'manufacturing' => 'products',
        'energy' => 'transport',
        'environment' => 'tourism',
    ],

    /*
    |--------------------------------------------------------------------------
    | HLS Streaming
    |--------------------------------------------------------------------------
    | Bitrate ladder for adaptive video streaming.
    */
    'hls' => [
        'ladder' => [
            ['height' => 360, 'bitrate' => 400000],
            ['height' => 480, 'bitrate' => 800000],
            ['height' => 720, 'bitrate' => 2500000],
            ['height' => 1080, 'bitrate' => 5000000],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shard Manager
    |--------------------------------------------------------------------------
    */
    'shard' => [
        'virtual_partitions' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */
    'payment_gateways' => [
        'mpesa' => \App\Services\MpesaService::class,
        'stripe' => \App\Services\StripePaymentDriver::class,
        'airtel' => \App\Services\AirtelMoneyDriver::class,
        'tkash' => \App\Services\TKashDriver::class,
        'bank_eft' => \App\Services\BankEftDriver::class,
        'crypto' => \App\Services\CryptoDriver::class,
        'manual' => \App\Services\ManualPaymentDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Category Fallbacks
    |--------------------------------------------------------------------------
    */
    'thumbnail_fallbacks' => [
        'nature' => 'nature', 'adventure' => 'adventure', 'agriculture' => 'agriculture',
        'culture' => 'culture', 'wildlife' => 'wildlife', 'beach' => 'beach',
        'historical' => 'historical', 'waterfall' => 'waterfall', 'hotel' => 'hotel',
        'resort' => 'resort', 'guest house' => 'hotel', 'conference' => 'conference',
        'restaurant' => 'restaurant', 'tourism' => 'tourism', 'tours' => 'tourism',
        'accommodation' => 'hotel', 'food' => 'agriculture', 'coffee' => 'agriculture',
        'tea' => 'agriculture', 'marine' => 'tourism', 'eco-tourism' => 'nature',
        'heritage' => 'historical', 'museum' => 'historical', 'leisure' => 'nature',
        'adventure' => 'adventure',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Color Palettes
    |--------------------------------------------------------------------------
    */
    'thumbnail_palettes' => [
        ['#0B1E57', '#1a3070'], ['#901C1E', '#b71c1c'], ['#0A1024', '#1a1a2e'],
        ['#046bd2', '#0EA5E9'], ['#2D6A4F', '#40916C'], ['#8B6914', '#FFCD05'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Institution Sync Sector Resolution
    |--------------------------------------------------------------------------
    */
    'sector_synonyms' => [
        'tourism' => ['tourism', 'tourist', 'travel', 'attraction', 'safari', 'beach', 'wildlife', 'park', 'museum', 'heritage'],
        'agriculture' => ['agriculture', 'farm', 'agri', 'crop', 'livestock', 'tea', 'coffee', 'farm', 'food'],
        'commerce' => ['commerce', 'trade', 'shop', 'market', 'retail', 'wholesale', 'export', 'product'],
        'hospitality' => ['hospitality', 'hotel', 'lodge', 'resort', 'restaurant', 'accommodation', 'guest', 'inn'],
        'education' => ['education', 'school', 'college', 'university', 'institute', 'academy', 'training', 'learning'],
        'health' => ['health', 'hospital', 'clinic', 'medical', 'doctor', 'healthcare', 'wellness', 'pharmacy'],
        'culture' => ['culture', 'cultural', 'heritage', 'art', 'museum', 'traditional', 'community', 'ceremony'],
        'transport' => ['transport', 'airport', 'port', 'terminal', 'station', 'road', 'highway', 'railway', 'logistics', 'shipping'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sector Pitch Service
    |--------------------------------------------------------------------------
    | Default descriptions for each sector.
    */
    'sector_pitches' => [
        'tourism' => 'Discover attractions, cultural sites, and natural wonders.',
        'hospitality' => 'Hotels, lodges, and accommodation for every traveller.',
        'farms' => 'Farms and agribusiness connecting producers to markets.',
        'agriculture' => 'Agriculture and farming at the heart of the county economy.',
        'products' => 'Bookable county end products and marketplace goods.',
        'commerce' => 'Business and trade opportunities across the county.',
        'education' => 'Schools, colleges, and training centres building the future.',
        'institutions' => 'Educational institutions and training centres.',
        'transport' => 'Transport hubs, logistics, and infrastructure networks.',
        'health' => 'Hospitals, clinics, and healthcare services.',
        'healthcare' => 'Healthcare facilities serving the community.',
        'culture' => 'Cultural sites, heritage, and community traditions.',
        'industries' => 'Manufacturing, energy, and industrial development.',
        'energy' => 'Energy production and infrastructure projects.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Agentic Loop
    |--------------------------------------------------------------------------
    | Signal priorities for the observer-decider-actor engine.
    */
    'agentic_loop' => [
        'priorities' => [
            'inactive_counties' => 3,
            'pending_orders' => 5,
            'stuck_payments' => 4,
            'system_health' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Travel Recommendations
    |--------------------------------------------------------------------------
    */
    'travel_recommendations' => [
        'default_markets' => ['London', 'Berlin', 'Toronto', 'Moscow'],
        'cold_threshold_celsius' => 14,
        'warm_counties' => ['mombasa', 'kwale', 'kilifi', 'lamu', 'malindi', 'taita-taveta'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Showcase Zoom Parameters
    |--------------------------------------------------------------------------
    | Ken Burns zoom levels for auto-generated screen showcase videos.
    | zoom_in  = end zoom for title card, start zoom for odd-indexed photos
    | zoom_out = start zoom for title card, end zoom for odd-indexed photos
    */
    'video_zooms' => [
        'zoom_in' => 1.15,
        'zoom_out' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    | Short TTL for admin dashboards so CRUD changes appear within minutes;
    | public pages use 6-hr default. Set a higher value for production with
    | explicit cache-busting hooks.
    */
    'cache_ttl' => [
        'admin' => 600,
        'public' => 21600,
        'media' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alpine.js Version
    |--------------------------------------------------------------------------
    */
    'alpine_version' => env('ALPINE_VERSION', '3.14.8'),

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'revenue_previous_ratio' => 0.85,
        'forecast_base_min' => 0.7,
        'forecast_growth_step' => 0.05,
        'forecast_multiplier' => 1.1,
        'forecast_round' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pool & Quality Scoring
    |--------------------------------------------------------------------------
    | All 20 algorithms from the reference implementation map here.
    | Alpha/beta control the contribution × quality power-law weighting:
    |   weight = contribution^alpha * quality^beta
    | (alpha=1, beta=0 degrades to pure proportional for launch month).
    */
'algorithms_service_url' => env('KICC_ALGORITHMS_URL', 'http://127.0.0.1:8400'),

    /*
    | Integration Layer (Node.js) — payment, freight, customs, FX, webhooks
    */
    'integration_service_url' => env('KICC_INTEGRATION_URL', 'http://127.0.0.1:8787'),

    'integration_webhook_secret' => env('KICC_INTEGRATION_WEBHOOK_SECRET', 'dev-secret'),
    'pool' => [
        'alpha' => 0.7,
        'beta' => 0.3,
        'holdback_pct' => 10.0,
        'equalisation_pct' => 0.5,
        'default_quality' => 0.5,
        'quality_weights' => [
            'delivery' => 0.25,
            'disputes' => 0.20,
            'trust' => 0.15,
            'completeness' => 0.15,
            'reviews' => 0.15,
            'media' => 0.10,
        ],
        'escrow_fee_rate' => 0.05,
        'commission_default_rate' => 0.03,
        'billing_vat_rate' => 0.16,
    ],
];