<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active AI Provider
    |--------------------------------------------------------------------------
    | One of: openrouter | groq | gemini
    */
    'default' => env('AI_PROVIDER', 'openrouter'),

    /*
    |--------------------------------------------------------------------------
    | OpenRouter
    |--------------------------------------------------------------------------
    */
    'openrouter' => [
        'endpoint' => 'https://openrouter.ai/api/v1',
        'api_key' => env('OPENROUTER_API_KEY', ''),
        'models' => [
            'mistralai/mistral-7b-instruct:free',
            'meta-llama/llama-3.1-8b-instruct:free',
            'google/gemma-2-9b-it:free',
            'microsoft/phi-3-mini-128k-instruct:free',
        ],
        'default_model' => 'mistralai/mistral-7b-instruct:free',
        'default_temperature' => 0.7,
        'default_max_tokens' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq
    |--------------------------------------------------------------------------
    */
    'groq' => [
        'endpoint' => 'https://api.groq.com/openai/v1',
        'api_key' => env('GROQ_API_KEY', ''),
        'models' => [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'mixtral-8x7b-32768',
            'gemma2-9b-it',
        ],
        'default_model' => 'llama-3.3-70b-versatile',
        'default_temperature' => 0.7,
        'default_max_tokens' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini
    | No public model-list endpoint — models are enumerated here only.
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
        'api_key' => env('GEMINI_API_KEY', ''),
        'models' => [
            'gemini-2.0-flash-lite',
            'gemini-1.5-flash-8b',
            'gemini-1.5-flash',
        ],
        'default_model' => 'gemini-2.0-flash-lite',
        'default_temperature' => 0.7,
        'default_max_tokens' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-list cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'models_cache_ttl' => 3600,

];
