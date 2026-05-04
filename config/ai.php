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
    ],

    /**
     * Get all available models for a specific provider
     */
    'getModels' => function (string $provider) {
        return config("ai.models.{$provider}", []);
    },

    /**
     * Get all providers with their models as options for Select dropdowns
     */
    'getProviderOptions' => function (string $provider) {
        $models = config("ai.models.{$provider}", []);
        return array_combine($models, $models);
    },
];
