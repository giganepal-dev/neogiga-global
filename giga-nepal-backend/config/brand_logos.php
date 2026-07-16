<?php

return [
    'disk' => env('BRAND_LOGO_DISK', 'public'),
    'max_download_bytes' => (int) env('BRAND_LOGO_MAX_DOWNLOAD_BYTES', 5 * 1024 * 1024),
    'min_width' => (int) env('BRAND_LOGO_MIN_WIDTH', 48),
    'minimum_auto_accept_confidence' => 0.92,

    // This registry is deliberately conservative. Unknown domains are proposed
    // for review rather than treated as official by name similarity alone.
    'official_domains' => [
        'adafruit' => 'adafruit.com',
        'arduino' => 'arduino.cc',
        'beagleboard' => 'beagleboard.org',
        'dfrobot' => 'dfrobot.com',
        'espressif' => 'espressif.com',
        'infineon' => 'infineon.com',
        'microchip' => 'microchip.com',
        'nvidia' => 'nvidia.com',
        'raspberry-pi' => 'raspberrypi.com',
        'seeed-studio' => 'seeedstudio.com',
        'sparkfun' => 'sparkfun.com',
        'stmicroelectronics' => 'st.com',
        'texas-instruments' => 'ti.com',
        'waveshare' => 'waveshare.com',
    ],

    'aliases' => [
        'te connectivity amp' => 'TE Connectivity',
        'te connectivity deutsch' => 'TE Connectivity',
        'measurement specialties' => 'TE Connectivity',
        'amp tyco electronics' => 'TE Connectivity',
        'tyco electronics' => 'TE Connectivity',
        'st microelectronics' => 'STMicroelectronics',
        'texas instruments inc' => 'Texas Instruments',
    ],
];
