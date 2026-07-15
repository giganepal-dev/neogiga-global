<?php

return [
    'source_code' => 'jlcpcb_parts_database',

    'minimum_data_quality_score' => (float) env('JLCPCB_PUBLICATION_MINIMUM_QUALITY', 0.65),

    'batch_size' => (int) env('JLCPCB_PUBLICATION_BATCH_SIZE', 250),

    'maximum_batch_size' => 1_000,

    'backup_root' => env('JLCPCB_BACKUP_ROOT', '/home/neogiga/backups'),

    'plan_root' => env(
        'JLCPCB_PUBLICATION_PLAN_ROOT',
        storage_path('app/private/jlcpcb-qualified-publication-plans'),
    ),

    'marketplace' => [
        'code' => 'GLOBAL',
        'currency' => 'USD',
    ],

    'audit' => [
        'source_notes' => 'Qualified by the governed JLCPCB publication gate using canonical identity, retained raw source data, a non-rejected distributor offer, an active GLOBAL/USD price, and an active locally served product image.',
        'confidence_level' => 'high_gate_confidence',
        'advisory_disclaimer' => 'Advisory only',
    ],
];
