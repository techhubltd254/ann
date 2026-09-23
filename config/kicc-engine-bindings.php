<?php

/* Canonical engine wiring for ALL pipelines — the validator asserts every
 * subsector entry matches these values exactly. */

return [
    'ledger' => [
        'service' => 'App\Kicc\Services\LedgerService',
        'hold_account' => 'escrow_clearing',
        'payable_account' => 'merchant_payable',
        'fee_credit_account' => 'fees_income',
        'lifecycle' => ['hold', 'capture', 'split', 'release', 'refund'],
    ],
    'mother_pool' => [
        'service' => 'App\Kicc\Services\MotherPoolService',
        'event' => 'contribution.recorded',
        'holdback_rate' => 0.10,
        'equalisation_rate' => 0.005,
        'quality_service' => 'App\Kicc\Services\QualityScoreService',
    ],
    'earning_locked_statuses' => ['licence_gated', 'blocked'],
];
