<?php

namespace App\Factories;

use App\Contracts\AiServiceInterface;
use App\Models\Store;
use App\Services\Ai\GrokService;
use App\Services\Ai\OpenAiService;
use Illuminate\Support\Facades\Log;

class AiServiceFactory
{
    /**
     * Supported models for each AI provider.
     */
    private const SUPPORTED_MODELS = [
        'openai' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo'],
        'grok' => ['grok-beta', 'grok-2', 'grok-3'],
    ];

    /**
     * Default models for each AI provider.
     */
    private const DEFAULT_MODELS = [
        'openai' => 'gpt-4o-mini',
        'grok' => 'grok-beta',
    ];

    /**
     * Create an AI service instance based on the store's configuration.
     *
     * @param Store $store
     * @return AiServiceInterface
     * @throws \Exception
     */
    public static function make(Store $store): AiServiceInterface
    {
        $provider = $store->ai_provider;
        $model = $store->ai_model;
        $apiKey = $store->ai_api_key;

        // Check if API key is missing
        if (!$apiKey) {
            throw new \Exception("Missing API Key for store: {$store->name}");
        }

        // Validate and get the model, with fallback to default if invalid
        $model = self::validateAndGetModel($provider, $model, $store);

        return match ($provider) {
            'openai' => new OpenAiService($apiKey, $model),
            'grok' => new GrokService($apiKey, $model),
            default => throw new \Exception("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Validate the model and return it, or a fallback default model if invalid.
     *
     * @param string $provider
     * @param ?string $model
     * @param Store $store
     * @return string
     * @throws \Exception
     */
    private static function validateAndGetModel(string $provider, ?string $model, Store $store): string
    {
        // Check if provider is supported
        if (!isset(self::SUPPORTED_MODELS[$provider])) {
            throw new \Exception("Unsupported AI provider: {$provider}");
        }

        // If model is provided and valid, use it
        if ($model && in_array($model, self::SUPPORTED_MODELS[$provider], true)) {
            return $model;
        }

        // Model is missing or invalid - use default and log warning
        $defaultModel = self::DEFAULT_MODELS[$provider];
        
        Log::warning('AI model invalid or missing, using default', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'provider' => $provider,
            'requested_model' => $model,
            'default_model' => $defaultModel,
            'supported_models' => self::SUPPORTED_MODELS[$provider],
        ]);

        return $defaultModel;
    }
}
