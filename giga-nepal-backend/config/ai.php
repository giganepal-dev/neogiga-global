<?php

return [
    'enabled' => env('AI_ENABLED', false),

    'providers' => [
        'openai' => [
            'enabled' => env('OPENAI_API_KEY') !== null,
            'api_key' => env('OPENAI_API_KEY'),
        ],
        'claude' => [
            'enabled' => env('ANTHROPIC_API_KEY') !== null,
            'api_key' => env('ANTHROPIC_API_KEY'),
        ],
        'gemini' => [
            'enabled' => env('GEMINI_API_KEY') !== null,
            'api_key' => env('GEMINI_API_KEY'),
        ],
        'qwen' => [
            'enabled' => env('QWEN_API_KEY') !== null,
            'api_key' => env('QWEN_API_KEY'),
        ],
        'deepseek' => [
            'enabled' => env('DEEPSEEK_API_KEY') !== null,
            'api_key' => env('DEEPSEEK_API_KEY'),
        ],
        'local_llama' => [
            'enabled' => env('LOCAL_LLAMA_BASE_URL') !== null,
            'base_url' => env('LOCAL_LLAMA_BASE_URL'),
        ],
    ],

    'commercial_action_confirmation_required' => true,

    'placeholder_surfaces' => [
        'floating_ai_assistant',
        'product_page_ai_assistant',
        'ai_bom_builder',
        'ai_project_builder',
        'ai_pos_chat',
        'lms_ai_tutor',
        'seller_ai_assistant',
        'admin_ai_console',
    ],

    'safety_topics' => [
        'battery',
        'mains_electricity',
        'robotics',
        'vehicles',
        'drones',
        'industrial_automation',
        'high_value_orders',
    ],
];
