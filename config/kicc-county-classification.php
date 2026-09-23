<?php

/* County classification: RPS & FNS + per-quadrant pipeline activation tiers.
 * Indicator weights drive quadrant assignment; activation_tiers map a
 * quadrant to the concrete pipeline codes that may go live in it. */

return [
    'weights' => [
        'rps' => [
            'population_index' => 0.60,
            'entity_index' => 0.20,
            'economic_zone_bonus' => 0.20,
        ],
        'fns' => [
            'density_index' => 0.40,
            'area_index' => 0.30,
            'economic_penalty' => 0.30,
        ],
    ],
    'thresholds' => ['rps_high' => 0.5, 'fns_high' => 0.5],

    /*
    | Activation tiers per quadrant — the pipeline codes permitted for a
    | county of that quadrant. Used by PipelineActivationService to decide
    | which of the 202 pipelines a county may run.
    |
    | markers: __ALL__ = every pipeline; __CORE_FLOWS__ = commercially viable
    | trade/tourism/commerce patterns; __INFRA__ = infrastructure-linked lines;
    | __SUBSIDY__ = subsidised government/health/basic-market lines.
    */
    'activation_tiers' => [
        'engine'   => ['__ALL__'],
        'growth'   => ['__CORE_FLOWS__'],
        'priority' => ['__INFRA__'],
        'anchor'   => ['__SUBSIDY__'],
    ],

    /*
    | Concrete pipeline appsets by marker. Parents (50) and subsectors (152)
    | are matched by prefix: a marker lists the parent codes and each parent
    | pulls its subsectors automatically.
    */
    'markers' => [
        // Commercially viable core flows — trade, tourism, commerce, community finance
        '__CORE_FLOWS__' => ['A1','A2','A3','A4','C1','C2','C3','C4','C5','G4','O1','P1','N2'],
        // Infrastructure-linked — energy, water, logistics, mobility
        '__INFRA__' => ['M1','M2','M3','M4','N1','N3','H1'],
        // Subsidised rails — government facilitation, health, education, basic market
        '__SUBSIDY__' => ['G1','G3','K1','K2','L1','L3','B1','B2'],
    ],
];
