<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | 預設使用的 AI 提供商：支援 'gemini' 或 'openai'
    | 可透過 .env 中的 AI_PROVIDER 進行切換
    |
    */
    'default' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    |
    | 各 Provider 的連線憑證與模型設定
    |
    */
    'providers' => [

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model'   => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        ],

        'openai' => [
            'api_key'  => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model'    => env('OPENAI_MODEL', 'gpt-5.6-luna'),
        ],

    ],

];
