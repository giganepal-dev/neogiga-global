<?php

return [
    /* Only these curated roots may be promoted on public category navigation. */
    'intended_root_slugs' => [
        'semiconductors', 'electronic-components', 'embedded-systems', 'sensors',
        'industrial-automation', 'iot-wireless', 'robotics', 'battery-technology',
        'power-electronics', 'power-storage', 'test-measurement', 'diy-maker-tools', 'renewable-energy',
        'manufacturing-equipment', 'raw-materials', 'rf-wireless', 'ev-components', 'ai-hardware',
    ],

    /* Canonical targets are existing NeoGiga slugs. These rules never create roots. */
    'synonyms' => [
        'op amp' => '266-operational-amplifiers',
        'op amps' => '266-operational-amplifiers',
        'operational amplifier' => '266-operational-amplifiers',
        'operational amplifiers' => '266-operational-amplifiers',
        'current sense amp' => '266-current-sense-amplifiers',
        'current sense amps' => '266-current-sense-amplifiers',
        'analog current sense amplifiers' => '266-current-sense-amplifiers',
        'instrumentation amp' => '266-instrumentation-amplifiers',
        'instrumentation amps' => '266-instrumentation-amplifiers',
        'difference amplifiers' => '266-differential-amplifiers',
        'signal conditioner' => 'signal-conditioners',
        '4 20 ma conditioner' => '4-20ma-signal-conditioners',
        'vfd' => 'variable-frequency-drives',
        'bms' => 'battery-management-systems',
        'dc dc converter' => 'dc-dc-converters',
        'programmable variable gain amplifiers' => 'variable-gain-amplifiers',
    ],
];
