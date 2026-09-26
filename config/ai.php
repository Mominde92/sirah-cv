<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI provider
    |--------------------------------------------------------------------------
    |
    | "anthropic", "openai" or "gemini". Leave empty to disable every AI
    | feature: the product works fully without AI, the buttons simply hide.
    |
    */

    'default' => env('AI_PROVIDER'),

    'providers' => [
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
            // Short, well-specified writing tasks: low effort keeps latency and cost down.
            'effort' => env('ANTHROPIC_EFFORT', 'low'),
            // Server-side refusal fallback (routes a declined request to the model's default fallback).
            'fallbacks' => env('ANTHROPIC_FALLBACKS', true),
            'timeout' => (int) env('ANTHROPIC_TIMEOUT', 60),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost estimates (USD per million tokens) for the admin AI-usage report
    |--------------------------------------------------------------------------
    */

    'pricing' => [
        'claude-opus-5' => ['input' => 5.00, 'output' => 25.00],
        'claude-sonnet-5' => ['input' => 2.00, 'output' => 10.00],
        'claude-haiku-4-5' => ['input' => 1.00, 'output' => 5.00],
    ],

];
