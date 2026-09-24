<?php

/* Regulatory mapping: which pipelines need which agency, and what unlocks them.
 * Used by AgencyDataService for auto-unlock and by the admin for documentation.
 */
return [
    'cbk' => [
        'label' => 'Central Bank of Kenya (CBK)',
        'pipelines' => ['B6', 'F1', 'F2', 'F3', 'F5'],
        'description' => 'CBK deposit-taking or credit-only licence. Required for all financing/ lending pipelines. ~60% of revenue ceiling.',
        'data_source' => 'cbk',
        'unlock_condition' => 'licence_granted',
    ],
    'ifmis' => [
        'label' => 'IFMIS / National Treasury',
        'pipelines' => ['G2', 'H1'],
        'description' => 'IFMIS REST API integration + OAuth2 + webhook reconciliation. Required for government procurement escrow.',
        'data_source' => 'ifmis',
        'unlock_condition' => 'api_credentials_verified',
    ],
    'ardhisasa' => [
        'label' => 'Ardhisasa / Ministry of Lands',
        'pipelines' => ['D3'],
        'description' => 'Ardhisasa API partnership + commercial API agreement. Required for title-verified real estate.',
        'data_source' => 'ardhisasa',
        'unlock_condition' => 'partnership_confirmed',
    ],
    'legal' => [
        'label' => 'Legislative Change',
        'pipelines' => ['K3'],
        'description' => 'Digital Health Act (2023) struck down by High Court. Requires new parliamentary bill or successful appeal.',
        'data_source' => null,
        'unlock_condition' => 'new_legislation',
    ],
];