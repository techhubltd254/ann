<?php

/* County classification: Revenue Potential Score (RPS) & Foundational/Strategic
 * Need Score (FNS). Indicators are normalised 0-100 before classification.
 * Adjust weights in config — never hardcode counties. */

return [
    'weights' => [
        'rps' => [
            'gmv_index' => 0.35,          // platform GMV potential
            'population_index' => 0.20,
            'business_density_index' => 0.20,
            'tourism_index' => 0.10,
            'infrastructure_index' => 0.15,
        ],
        'fns' => [
            'poverty_index' => 0.40,
            'service_gap_index' => 0.30,
            'infrastructure_deficit_index' => 0.30,
        ],
    ],
    'thresholds' => [
        'rps_high' => 60.0,
        'fns_high' => 60.0,
    ],
    // activation tiers: which pipeline codes may go live per quadrant
    'activation_tiers' => [
        'engine' => ['all'],
        'growth' => ['top12'],
        'priority' => ['infrastructure_linked'],
        'anchor' => ['subsidised_rails'],
    ],
];
