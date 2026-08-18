<?php
/**
 * KICC Advertising Revenue Model
 *
 * Revenue targets: $2M/month at launch
 *   1. Escrow commission (2%): $100M monthly transactions = $2M
 *   2. Advertising (4 placements): $40K/month
 *   3. Subscriptions (3 tiers): $50K/month
 *
 * Total addressable: $2.09M/month
 */

return [
    'commission_rate' => env('ESCROW_COMMISSION_RATE', 0.02), // 2%

    'placements' => [
        'featured_placement' => [
            'name' => 'Featured Placement',
            'price_monthly' => 500000, // KES 500K/month
            'price_usd' => 4000,
            'description' => 'Hero position on homepage for 30 days',
        ],
        'county_comarketing' => [
            'name' => 'County Co-Marketing',
            'price_monthly' => 250000, // KES 250K/month
            'price_usd' => 2000,
            'description' => 'Premium placement on county detail pages',
        ],
        'referral' => [
            'name' => 'Referral / Click-Out',
            'price_monthly' => 150000, // KES 150K/month
            'price_usd' => 1200,
            'description' => 'Sponsored click-out from exhibition pages',
        ],
        'livestream_banner' => [
            'name' => 'Livestream Banner',
            'price_monthly' => 300000, // KES 300K/month
            'price_usd' => 2400,
            'description' => 'Banner on all livestream channels',
        ],
    ],

    'subscriptions' => [
        'basic' => ['price' => 500, 'features' => 'Booth listing, basic analytics'],
        'premium' => ['price' => 2500, 'features' => 'Booth + livestream, full analytics'],
        'enterprise' => ['price' => 10000, 'features' => 'All features, API access, priority support'],
    ],
];
