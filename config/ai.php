<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | The name of the provider key in `providers` below that agents will use
    | when no explicit provider is given at prompt time.
    */
    'default' => env('AI_DEFAULT_PROVIDER', 'openrouter'),

    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'openai',
    'default_for_reranking' => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    | Standard Laravel AI SDK provider definitions.
    */
    'providers' => [
        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],

        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
        ],

        'deepseek' => [
            'driver' => 'deepseek',
            'key' => env('DEEPSEEK_API_KEY'),
        ],

        'azure' => [
            'driver' => 'azure',
            'key' => env('AZURE_OPENAI_API_KEY'),
            'url' => env('AZURE_OPENAI_URL'),
            'api_version' => env('AZURE_OPENAI_API_VERSION', '2024-10-21'),
            'deployment' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
        ],

        'mistral' => [
            'driver' => 'mistral',
            'key' => env('MISTRAL_API_KEY'),
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => env('XAI_API_KEY'),
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
        ],

        'ollama' => [
            'driver' => 'ollama',
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Metadata (used by AiSetting UI and AiSetting model defaults)
    |--------------------------------------------------------------------------
    | Not part of the SDK — used by our own AiSetting / UI layer.
    */
    'meta' => [
        'openrouter' => [
            'default_model' => 'nvidia/nemotron-3-super-120b-a12b:free',
            'models' => [
                'nvidia/nemotron-3-super-120b-a12b:free',
                'minimax/minimax-m2.5:free',
                'qwen/qwen3-next-80b-a3b-instruct:free',
                'nvidia/nemotron-3-nano-30b-a3b:free',
            ],
        ],
        'groq' => [
            'default_model' => 'llama-3.3-70b-versatile',
            'models' => [
                'llama-3.3-70b-versatile',
                'llama-3.1-8b-instant',
                'meta-llama/llama-4-scout-17b-16e-instruct',
                'qwen/qwen3-32b',
                'moonshotai/kimi-k2-instruct',
            ],
        ],
        'gemini' => [
            'default_model' => 'gemini-2.0-flash-lite',
            'models' => [
                'gemini-2.0-flash-lite',
                'gemini-1.5-flash-8b',
                'gemini-1.5-flash',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-list cache TTL (seconds) — used by the legacy AiSettings page
    |--------------------------------------------------------------------------
    */
    'models_cache_ttl' => 3600,

];
