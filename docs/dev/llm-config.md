# LLM Config (config/llm.php)

Paste this file into: `config/llm.php`

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default provider and fallback chain
    |--------------------------------------------------------------------------
    */
    'default' => env('LLM_DEFAULT_PROVIDER', 'openai'),

    // Provider fallback order when the selected provider fails or is rate limited
    'fallback_order' => array_filter(explode(',', env('LLM_FALLBACK_ORDER', 'openai,gemini,anthropic'))),

    /*
    |--------------------------------------------------------------------------
    | Provider definitions
    |--------------------------------------------------------------------------
    | Note: org/user API keys can override these platform keys.
    */
    'providers' => [

        'openai' => [
            'enabled' => env('OPENAI_ENABLED', true),
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
            'timeout_seconds' => env('OPENAI_TIMEOUT', 20),
        ],

        'gemini' => [
            'enabled' => env('GEMINI_ENABLED', true),
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout_seconds' => env('GEMINI_TIMEOUT', 20),
        ],

        'anthropic' => [
            'enabled' => env('ANTHROPIC_ENABLED', true),
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-latest'),
            'timeout_seconds' => env('ANTHROPIC_TIMEOUT', 20),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output schema requirements
    |--------------------------------------------------------------------------
    */
    'summary_schema' => [
        'risk_level' => ['low', 'medium', 'high'],
        'risk_score_min' => 0,
        'risk_score_max' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompting safety
    |--------------------------------------------------------------------------
    | Only allow structured metrics+findings, never raw files.
    */
    'allow_raw_files' => false,

];
```

## Notes

- Per-organization API keys are stored encrypted in the database (`api_keys` table).
- Router chooses provider:
    1. org preferred provider
    2. fallback order
    3. rules-only summary if all fail
- Always request strict JSON output and validate before saving.
