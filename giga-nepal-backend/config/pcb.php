<?php

return [
    'enabled' => env('PCB_PLATFORM_ENABLED', true),
    'domain' => env('PCB_DOMAIN', 'pcb.neogiga.com'),
    'storage_disk' => env('PCB_STORAGE_DISK', 'local'),
    'storage_path' => env('PCB_STORAGE_PATH', 'pcb-projects'),
    'max_file_size_mb' => (int) env('PCB_MAX_FILE_SIZE_MB', 100),
    'max_archive_entries' => (int) env('PCB_MAX_ARCHIVE_ENTRIES', 2000),
    'max_archive_uncompressed_mb' => (int) env('PCB_MAX_ARCHIVE_UNCOMPRESSED_MB', 500),
    'max_archive_ratio' => (int) env('PCB_MAX_ARCHIVE_RATIO', 100),
    'download_link_minutes' => (int) env('PCB_DOWNLOAD_LINK_MINUTES', 15),
    'allowed_extensions' => [
        'gerber' => ['zip'],
        'bom' => ['csv', 'xls', 'xlsx', 'zip'],
        'cpl' => ['csv', 'txt', 'zip'],
        'schematic' => ['pdf', 'zip'],
        'pcb_source' => ['zip', 'kicad_pcb', 'brd'],
        'step' => ['step', 'stp', 'zip'],
        'assembly_drawing' => ['pdf', 'zip'],
        'other' => ['pdf', 'zip', 'csv', 'txt'],
    ],
];
