<?php

return [

    /*
    | Screen presets — control video generation behavior per screen type.
    | Mirrors the Python video_service.py SCREEN_PRESETS.
    */
    'presets' => [
        'county_main' => [
            'dur_range' => [180, 300],
            'img_range' => [30, 60],
            'clip_sec' => 6,
            'label' => 'county',
            'title' => true,
        ],
        'county_sub' => [
            'dur_range' => [60, 120],
            'img_range' => [15, 30],
            'clip_sec' => 5,
            'label' => 'sector',
            'title' => true,
        ],
        'sector_pavilion' => [
            'dur_range' => [30, 60],
            'img_range' => [8, 15],
            'clip_sec' => 4,
            'label' => 'sector',
            'title' => false,
        ],
        'hero_wall' => [
            'dur_range' => [60, 120],
            'img_range' => [15, 30],
            'clip_sec' => 5,
            'label' => 'auto',
            'title' => true,
        ],
        'info_kiosk' => [
            'dur_range' => [15, 30],
            'img_range' => [5, 8],
            'clip_sec' => 3,
            'label' => 'none',
            'title' => false,
        ],
        'hallway' => [
            'dur_range' => [120, 180],
            'img_range' => [30, 45],
            'clip_sec' => 5,
            'label' => 'county',
            'title' => true,
        ],
    ],

    /*
    | Default screens seeded into the database.
    | Matches the Python register_images.py seed_screens().
    */
    'default_screens' => [
        // County main booths
        ['id' => 'county_main_14', 'label' => 'Mombasa Main Booth', 'location' => 'Hall A', 'county_id' => 'mombasa', 'sector_id' => null, 'duration' => 240, 'min' => 15, 'max' => 60, 'refresh' => 60],
        ['id' => 'county_main_15', 'label' => 'Kilifi Main Booth', 'location' => 'Hall A', 'county_id' => 'kilifi', 'sector_id' => null, 'duration' => 240, 'min' => 15, 'max' => 60, 'refresh' => 60],
        ['id' => 'county_sub_14', 'label' => 'Mombasa Sub-Booth', 'location' => 'Hall A', 'county_id' => 'mombasa', 'sector_id' => null, 'duration' => 90, 'min' => 15, 'max' => 30, 'refresh' => 60],
        ['id' => 'county_sub_15', 'label' => 'Kilifi Sub-Booth', 'location' => 'Hall A', 'county_id' => 'kilifi', 'sector_id' => null, 'duration' => 90, 'min' => 15, 'max' => 30, 'refresh' => 60],
        // Sector pavilions
        ['id' => 'sector_tourism', 'label' => 'Tourism Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'tourism', 'duration' => 45, 'min' => 8, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_agriculture', 'label' => 'Agriculture Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'agriculture', 'duration' => 45, 'min' => 6, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_transport', 'label' => 'Transport Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'transport', 'duration' => 45, 'min' => 6, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_manufacturing', 'label' => 'Manufacturing Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'manufacturing', 'duration' => 45, 'min' => 2, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_energy', 'label' => 'Energy Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'energy', 'duration' => 45, 'min' => 2, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_education', 'label' => 'Education Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'education', 'duration' => 45, 'min' => 2, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_health', 'label' => 'Health Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'health', 'duration' => 45, 'min' => 1, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_fisheries', 'label' => 'Fisheries Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'fisheries', 'duration' => 45, 'min' => 5, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_culture', 'label' => 'Culture Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'culture', 'duration' => 45, 'min' => 8, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_trade', 'label' => 'Trade Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'trade', 'duration' => 45, 'min' => 6, 'max' => 15, 'refresh' => 120],
        ['id' => 'sector_environment', 'label' => 'Environment Pavilion', 'location' => 'Hall B', 'county_id' => null, 'sector_id' => 'environment', 'duration' => 45, 'min' => 5, 'max' => 15, 'refresh' => 120],
        // Common area
        ['id' => 'hero_entrance', 'label' => 'Hero Entrance Wall', 'location' => 'Main Lobby', 'county_id' => null, 'sector_id' => null, 'duration' => 90, 'min' => 15, 'max' => 30, 'refresh' => 30],
        ['id' => 'hallway_east', 'label' => 'East Hallway Display', 'location' => 'Hall A Corridor', 'county_id' => null, 'sector_id' => null, 'duration' => 150, 'min' => 30, 'max' => 45, 'refresh' => 120],
        ['id' => 'hallway_west', 'label' => 'West Hallway Display', 'location' => 'Hall B Corridor', 'county_id' => null, 'sector_id' => null, 'duration' => 150, 'min' => 30, 'max' => 45, 'refresh' => 120],
    ],

    /*
    | County 3D map positions (normalized for Three.js).
    | From Python county_data.py.
    */
    'county_positions' => [
        'mombasa'      => ['x' => -0.8, 'y' => 0.0, 'z' => 1.6, 'region' => 'Coast', 'scene' => 'coast'],
        'kilifi'       => ['x' => -0.6, 'y' => 0.1, 'z' => 1.3, 'region' => 'Coast', 'scene' => 'coast'],
        'kwale'        => ['x' => -0.9, 'y' => 0.0, 'z' => 1.9, 'region' => 'Coast', 'scene' => 'coast'],
        'tana_river'   => ['x' => 0.2,  'y' => 0.2, 'z' => 1.0, 'region' => 'Coast', 'scene' => 'savanna'],
        'lamu'         => ['x' => 0.3,  'y' => 0.0, 'z' => 0.7, 'region' => 'Coast', 'scene' => 'coast'],
        'taita_makta'  => ['x' => -0.6, 'y' => -0.1, 'z' => 1.9, 'region' => 'Coast', 'scene' => 'highland'],
        'garissa'      => ['x' => 0.8,  'y' => 0.3, 'z' => 0.5, 'region' => 'Upper Eastern', 'scene' => 'savanna'],
        'wajir'        => ['x' => 1.4,  'y' => 0.5, 'z' => 0.2, 'region' => 'Upper Eastern', 'scene' => 'savanna'],
        'mandera'      => ['x' => 1.9,  'y' => 0.7, 'z' => -0.3, 'region' => 'Upper Eastern', 'scene' => 'savanna'],
        'marsabit'     => ['x' => 1.2,  'y' => 0.8, 'z' => -0.2, 'region' => 'Upper Eastern', 'scene' => 'savanna'],
        'isiolo'       => ['x' => 0.6,  'y' => 0.5, 'z' => 0.1, 'region' => 'Upper Eastern', 'scene' => 'savanna'],
        'meru'         => ['x' => 0.4,  'y' => 0.3, 'z' => 0.2, 'region' => 'Upper Eastern', 'scene' => 'highland'],
        'tharaka_nithi' => ['x' => 0.3, 'y' => 0.2, 'z' => 0.3, 'region' => 'Upper Eastern', 'scene' => 'highland'],
        'embu'         => ['x' => 0.3,  'y' => 0.1, 'z' => 0.4, 'region' => 'Upper Eastern', 'scene' => 'highland'],
        'kitui'        => ['x' => 0.5,  'y' => 0.0, 'z' => 0.6, 'region' => 'Lower Eastern', 'scene' => 'savanna'],
        'makueni'      => ['x' => 0.3,  'y' => -0.2, 'z' => 0.8, 'region' => 'Lower Eastern', 'scene' => 'savanna'],
        'machakos'     => ['x' => 0.0,  'y' => -0.1, 'z' => 0.8, 'region' => 'Lower Eastern', 'scene' => 'highland'],
        'nairobi'      => ['x' => -0.1, 'y' => -0.1, 'z' => 0.6, 'region' => 'Nairobi', 'scene' => 'urban'],
        'kiambu'       => ['x' => -0.2, 'y' => 0.0, 'z' => 0.5, 'region' => 'Central', 'scene' => 'highland'],
        'muranga'      => ['x' => -0.1, 'y' => 0.1, 'z' => 0.4, 'region' => 'Central', 'scene' => 'highland'],
        'nyeri'        => ['x' => -0.2, 'y' => 0.2, 'z' => 0.3, 'region' => 'Central', 'scene' => 'highland'],
        'kirinyaga'    => ['x' => 0.0,  'y' => 0.2, 'z' => 0.3, 'region' => 'Central', 'scene' => 'highland'],
        'nyandarua'    => ['x' => -0.4, 'y' => 0.3, 'z' => 0.2, 'region' => 'Central', 'scene' => 'highland'],
        'laikipia'     => ['x' => -0.1, 'y' => 0.5, 'z' => 0.0, 'region' => 'Central', 'scene' => 'savanna'],
        'nakuru'       => ['x' => -0.4, 'y' => 0.2, 'z' => 0.2, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'kajiado'      => ['x' => -0.3, 'y' => -0.3, 'z' => 1.1, 'region' => 'Rift Valley', 'scene' => 'savanna'],
        'narok'        => ['x' => -0.6, 'y' => -0.1, 'z' => 0.6, 'region' => 'Rift Valley', 'scene' => 'savanna'],
        'kericho'      => ['x' => -0.7, 'y' => 0.1, 'z' => 0.2, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'bomet'        => ['x' => -0.6, 'y' => 0.0, 'z' => 0.4, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'kakamega'     => ['x' => -1.0, 'y' => 0.3, 'z' => -0.2, 'region' => 'Western', 'scene' => 'highland'],
        'vihiga'       => ['x' => -1.0, 'y' => 0.2, 'z' => -0.1, 'region' => 'Western', 'scene' => 'highland'],
        'busia'        => ['x' => -0.8, 'y' => 0.4, 'z' => -0.4, 'region' => 'Western', 'scene' => 'urban'],
        'bungoma'      => ['x' => -0.9, 'y' => 0.5, 'z' => -0.3, 'region' => 'Western', 'scene' => 'highland'],
        'kisumu'       => ['x' => -0.9, 'y' => 0.1, 'z' => 0.1, 'region' => 'Nyanza', 'scene' => 'urban'],
        'siaya'        => ['x' => -0.7, 'y' => 0.3, 'z' => -0.1, 'region' => 'Nyanza', 'scene' => 'highland'],
        'homa_bay'     => ['x' => -0.9, 'y' => 0.0, 'z' => 0.3, 'region' => 'Nyanza', 'scene' => 'coast'],
        'migori'       => ['x' => -1.0, 'y' => -0.1, 'z' => 0.5, 'region' => 'Nyanza', 'scene' => 'highland'],
        'kisii'        => ['x' => -0.9, 'y' => -0.1, 'z' => 0.4, 'region' => 'Nyanza', 'scene' => 'highland'],
        'nyamira'      => ['x' => -0.8, 'y' => 0.0, 'z' => 0.3, 'region' => 'Nyanza', 'scene' => 'highland'],
        'uet'          => ['x' => -0.6, 'y' => 0.3, 'z' => 0.0, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'nandi'        => ['x' => -0.6, 'y' => 0.2, 'z' => 0.1, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'baringo'      => ['x' => -0.3, 'y' => 0.5, 'z' => -0.1, 'region' => 'Rift Valley', 'scene' => 'savanna'],
        'turkana'      => ['x' => 0.3,  'y' => 1.0, 'z' => -0.5, 'region' => 'Rift Valley', 'scene' => 'savanna'],
        'west_pokot'   => ['x' => -0.5, 'y' => 0.7, 'z' => -0.4, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'samburu'      => ['x' => 0.2,  'y' => 0.7, 'z' => -0.2, 'region' => 'Rift Valley', 'scene' => 'savanna'],
        'trans_nzoia'  => ['x' => -0.7, 'y' => 0.6, 'z' => -0.4, 'region' => 'Rift Valley', 'scene' => 'highland'],
        'elgeyo_karak' => ['x' => -0.5, 'y' => 0.6, 'z' => -0.3, 'region' => 'Rift Valley', 'scene' => 'highland'],
    ],
];
