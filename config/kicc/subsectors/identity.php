<?php

/* KICC subsector pipelines — sector: identity (3 entries)
 * Every entry is wired to the General Ledger (LedgerService) and the
 * Mother-Pool (MotherPoolService) via the 'engine' block, and inherits
 * phase/status/regulators/tables from its parent pipeline.
 */

return [
    [
        'code' => 'P1.1',
        'parent' => 'P1',
        'sector' => 'identity',
        'subsector' => 'Business KYB checks',
        'slug' => 'kyb-checks',
        'phase' => '1',
        'status' => 'absent',
        'economics' => ['model' => 'flat_fee', 'flat_fee_kes' => 0],
        'regulators' => ["ODPC"],
        'tables' => ["kyb_verification_requests"],
        'kill_criteria' => ['max_dispute_rate_pct' => 3.0, 'min_fill_rate_pct' => 70.0, 'consecutive_months' => 2],
        'engine' => [
            'ledger' => [
                'service' => 'App\\Kicc\\Services\\LedgerService',
                'pipeline_code' => 'P1.1',
                'hold_account' => 'escrow_clearing',
                'payable_account' => 'merchant_payable',
                'fee_credit_account' => 'fees_income',
                'lifecycle' => ['hold', 'capture', 'split', 'release', 'refund'],
                'earning_locked' => false,
            ],
            'mother_pool' => [
                'service' => 'App\\Kicc\\Services\\MotherPoolService',
                'event' => 'contribution.recorded',
                'holdback_rate' => 0.10,
                'equalisation_rate' => 0.005,
                'quality_service' => 'App\\Kicc\\Services\\QualityScoreService',
            ],
        ],
    ],
    [
        'code' => 'P1.2',
        'parent' => 'P1',
        'sector' => 'identity',
        'subsector' => 'Individual KYC checks',
        'slug' => 'kyc-individual',
        'phase' => '1',
        'status' => 'absent',
        'economics' => ['model' => 'flat_fee', 'flat_fee_kes' => 0],
        'regulators' => ["ODPC"],
        'tables' => ["kyb_verification_requests"],
        'kill_criteria' => ['max_dispute_rate_pct' => 3.0, 'min_fill_rate_pct' => 70.0, 'consecutive_months' => 2],
        'engine' => [
            'ledger' => [
                'service' => 'App\\Kicc\\Services\\LedgerService',
                'pipeline_code' => 'P1.2',
                'hold_account' => 'escrow_clearing',
                'payable_account' => 'merchant_payable',
                'fee_credit_account' => 'fees_income',
                'lifecycle' => ['hold', 'capture', 'split', 'release', 'refund'],
                'earning_locked' => false,
            ],
            'mother_pool' => [
                'service' => 'App\\Kicc\\Services\\MotherPoolService',
                'event' => 'contribution.recorded',
                'holdback_rate' => 0.10,
                'equalisation_rate' => 0.005,
                'quality_service' => 'App\\Kicc\\Services\\QualityScoreService',
            ],
        ],
    ],
    [
        'code' => 'P1.3',
        'parent' => 'P1',
        'sector' => 'identity',
        'subsector' => 'Partner API verification',
        'slug' => 'api-partners',
        'phase' => '1',
        'status' => 'absent',
        'economics' => ['model' => 'flat_fee', 'flat_fee_kes' => 0],
        'regulators' => ["ODPC"],
        'tables' => ["kyb_verification_requests"],
        'kill_criteria' => ['max_dispute_rate_pct' => 3.0, 'min_fill_rate_pct' => 70.0, 'consecutive_months' => 2],
        'engine' => [
            'ledger' => [
                'service' => 'App\\Kicc\\Services\\LedgerService',
                'pipeline_code' => 'P1.3',
                'hold_account' => 'escrow_clearing',
                'payable_account' => 'merchant_payable',
                'fee_credit_account' => 'fees_income',
                'lifecycle' => ['hold', 'capture', 'split', 'release', 'refund'],
                'earning_locked' => false,
            ],
            'mother_pool' => [
                'service' => 'App\\Kicc\\Services\\MotherPoolService',
                'event' => 'contribution.recorded',
                'holdback_rate' => 0.10,
                'equalisation_rate' => 0.005,
                'quality_service' => 'App\\Kicc\\Services\\QualityScoreService',
            ],
        ],
    ],
];
