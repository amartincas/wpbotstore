<?php

return [
    /**
     * Available AI models for each provider.
     * Define models as comma-separated values in .env
     * e.g., AI_MODELS_OPENAI=gpt-4o,gpt-4o-mini
     */
    'models' => [
        'openai' => array_filter(array_map('trim', explode(',', env('AI_MODELS_OPENAI', 'gpt-4o,gpt-4o-mini')))),
        'grok' => array_filter(array_map('trim', explode(',', env('AI_MODELS_GROK', 'grok-beta')))),
        'gemini' => array_filter(array_map('trim', explode(',', env('AI_MODELS_GEMINI', 'gemini-2.5-flash,gemini-2.5-pro,gemini-3-flash-preview')))),   
    ],

    /**
     * Provider options for Select dropdowns
     *
     * This avoids storing closures in config, which breaks config:caching.
     */
    'provider_options' => [
        'openai' => array_combine(
            array_filter(array_map('trim', explode(',', env('AI_MODELS_OPENAI', 'gpt-4o,gpt-4o-mini')))),
            array_filter(array_map('trim', explode(',', env('AI_MODELS_OPENAI', 'gpt-4o,gpt-4o-mini'))))
        ) ?: [],
        'grok' => array_combine(
            array_filter(array_map('trim', explode(',', env('AI_MODELS_GROK', 'grok-beta')))),
            array_filter(array_map('trim', explode(',', env('AI_MODELS_GROK', 'grok-beta'))))
        ) ?: [],
        'gemini' => array_combine(
            array_filter(array_map('trim', explode(',', env('AI_MODELS_GEMINI', 'gemini-2.5-flash,gemini-2.5-pro,gemini-3-flash-preview')))),
            array_filter(array_map('trim', explode(',', env('AI_MODELS_GEMINI', 'gemini-2.5-flash,gemini-2.5-pro,gemini-3-flash-preview'))))
        ) ?: [],
    ],
];
