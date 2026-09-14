<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Experience Pipeline Configuration
    |--------------------------------------------------------------------------
    | Controls the order and behavior of pipeline stages when a user
    | builds a complete travel experience from any anchor point
    | (attraction, county, product, or institution).
    */
    'pipeline' => [
        'stages' => ['context', 'correlation', 'builder', 'itinerary', 'booking', 'receipt'],
        'stop_on_failure' => false,
    ],

    'correlation' => [
        'weights' => [
            'geo' => 0.25,
            'diversity' => 0.35,
            'review' => 0.20,
            'completeness' => 0.10,
            'sector' => 0.10,
        ],
        'max_places_to_visit' => 4,
        'max_places_to_stay' => 3,
        'max_transport_options' => 3,
    ],

    'pricing' => [
        'default_entry_fee' => 500,
        'addons' => [
            'transport' => ['label' => 'Private return transfer', 'price' => 3500, 'per_guest' => false],
            'flight' => ['label' => 'Domestic flight (NBO → destination)', 'price' => 8500, 'per_guest' => true],
            'helicopter' => ['label' => 'Helicopter scenic transfer', 'price' => 45000, 'per_guest' => true],
            'restaurant' => ['label' => 'Lunch at partner restaurant', 'price' => 2000, 'per_guest' => true],
            'guide' => ['label' => 'Private tour guide (full day)', 'price' => 5000, 'per_guest' => false],
            'insurance' => ['label' => 'Travel insurance', 'price' => 1500, 'per_guest' => true],
        ],
        'package_discounts' => [
            3 => 5,    // 3+ items → 5% off
            5 => 10,   // 5+ items → 10% off
            7 => 15,   // 7+ items → 15% off
        ],
    ],

    'itinerary' => [
        'default_days' => 3,
        'max_days' => 14,
        'ai_model' => env('ITINERARY_AI_MODEL', 'openai/gpt-4o-mini'),
        'ai_temperature' => 0.7,
    ],

    'experience_types' => [
        'culture' => ['label' => 'Culture & Heritage', 'emoji' => '🏛', 'color' => '#8B4513'],
        'food' => ['label' => 'Food & Dining', 'emoji' => '🍽', 'color' => '#FF6347'],
        'nature' => ['label' => 'Nature & Outdoors', 'emoji' => '🌿', 'color' => '#2E8B57'],
        'hotel' => ['label' => 'Places to Stay', 'emoji' => '🏨', 'color' => '#4A90D9'],
        'shopping' => ['label' => 'Shopping & Markets', 'emoji' => '🛍', 'color' => '#FF69B4'],
        'entertainment' => ['label' => 'Entertainment', 'emoji' => '🎬', 'color' => '#9370DB'],
        'transport' => ['label' => 'Transport', 'emoji' => '🚗', 'color' => '#708090'],
        'education' => ['label' => 'Education', 'emoji' => '📚', 'color' => '#DAA520'],
        'industry' => ['label' => 'Industry & Trade', 'emoji' => '🏭', 'color' => '#696969'],
        'wellness' => ['label' => 'Wellness & Spa', 'emoji' => '💆', 'color' => '#20B2AA'],
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
];