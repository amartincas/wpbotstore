<?php

namespace App\Services\AI;

use App\Models\Store;
use App\Services\Ai\OpenAiService;
use App\Services\Ai\GrokService;
use App\Contracts\AiServiceInterface;
use Exception;

class AIServiceFactory
{
    /**
     * Create an AI service instance based on the store's ai_provider.
     *
     * @param Store $store
     * @return AIServiceInterface
     * @throws Exception
     */
    public static function make(Store $store): AIServiceInterface
    {
        return match ($store->ai_provider) {
            'openai' => new OpenAiService($store->ai_api_key, $store->ai_model),
            'grok' => new GrokService($store->ai_api_key, $store->ai_model),
            default => throw new Exception("Unsupported AI provider: {$store->ai_provider}"),
        };
    }

    /**
     * Create an AI service from provider string and model directly.
     *
     * @param string $provider
     * @param string $model
     * @param string $apiKey
     * @return AIServiceInterface
     * @throws Exception
     */
    public static function makeFromProvider(string $provider, string $model, string $apiKey): AIServiceInterface
    {
        return match ($provider) {
            'openai' => new OpenAiService($apiKey, $model),
            'grok' => new GrokService($apiKey, $model),
            default => throw new Exception("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Get all supported AI providers.
     *
     * @return array
     */
    public static function supportedProviders(): array
    {
        return ['openai', 'grok'];
    }

    /**
     * Check if a provider is supported.
     *
     * @param string $provider
     * @return bool
     */
    public static function isSupported(string $provider): bool
    {
        return in_array($provider, self::supportedProviders());
    }
}
