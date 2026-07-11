<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PCB Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the pcb.neogiga.com platform integration.
    |
    */

    // Platform settings
    'enabled' => env('PCB_PLATFORM_ENABLED', true),
    'domain' => env('PCB_DOMAIN', 'pcb.neogiga.com'),
    
    // File upload settings
    'max_file_size_mb' => env('PCB_MAX_FILE_SIZE_MB', 100),
    'max_gerber_bundle_mb' => env('PCB_MAX_GERBER_BUNDLE_MB', 100),
    'allowed_file_types' => [
        'gerber' => ['zip'],
        'schematic' => ['sch', 'pdf', 'zip'],
        'bom' => ['csv', 'xls', 'xlsx'],
        'cpl' => ['csv', 'txt'],
        'drill' => ['txt', 'drl', 'zip'],
        'cad' => ['step', 'stp', 'dxf', 'zip'],
        'other' => ['pdf', 'png', 'jpg', 'zip'],
    ],
    
    // Security settings
    'scan_files' => env('PCB_SCAN_FILES', true),
    'encrypt_files' => env('PCB_ENCRYPT_FILES', true),
    'zip_bomb_ratio_threshold' => env('PCB_ZIP_BOMB_RATIO', 100),
    
    // Storage
    'storage_disk' => env('PCB_STORAGE_DISK', 'private'),
    'storage_path' => env('PCB_STORAGE_PATH', 'pcb-projects'),
    
    // Project settings
    'project_code_prefix' => env('PCB_PROJECT_CODE_PREFIX', 'PCB-'),
    'default_marketplace' => env('PCB_DEFAULT_MARKETPLACE', 'global'),
    
    // Status mappings
    'project_statuses' => [
        'draft',
        'requirements_pending',
        'design_requested',
        'design_in_progress',
        'design_review',
        'design_approved',
        'files_ready',
        'quote_pending',
        'quoted',
        'awaiting_approval',
        'ordered',
        'manufacturing',
        'inspection',
        'shipped',
        'completed',
        'on_hold',
        'cancelled',
    ],
    
    // Role permissions
    'roles' => [
        'owner' => ['edit', 'upload_files', 'approve', 'invite', 'delete'],
        'admin' => ['edit', 'upload_files', 'approve', 'invite'],
        'engineer' => ['edit', 'upload_files', 'approve'],
        'member' => ['view'],
        'viewer' => ['view'],
    ],
    
    // Queue configuration
    'queues' => [
        'file_scan' => env('PCB_QUEUE_FILE_SCAN', 'pcb-file-scan'),
        'file_process' => env('PCB_QUEUE_FILE_PROCESS', 'pcb-file-process'),
        'gerber_parse' => env('PCB_QUEUE_GERBER_PARSE', 'pcb-gerber-parse'),
        'gerber_preview' => env('PCB_QUEUE_GERBER_PREVIEW', 'pcb-preview'),
        'bom_import' => env('PCB_QUEUE_BOM_IMPORT', 'pcb-bom-import'),
        'cpl_import' => env('PCB_QUEUE_CPL_IMPORT', 'pcb-cpl-import'),
        'component_match' => env('PCB_QUEUE_COMPONENT_MATCH', 'pcb-component-match'),
        'dfm_check' => env('PCB_QUEUE_DFM_CHECK', 'pcb-dfm'),
        'price_calc' => env('PCB_QUEUE_PRICE_CALC', 'pcb-price'),
        'notification' => env('PCB_QUEUE_NOTIFICATION', 'pcb-notification'),
    ],
    
    // Analysis settings
    'analysis' => [
        'auto_analyze_gerber' => env('PCB_AUTO_ANALYZE_GERBER', true),
        'parser_version' => env('PCB_PARSER_VERSION', '1.0.0'),
    ],
    
];
