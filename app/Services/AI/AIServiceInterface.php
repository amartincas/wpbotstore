<?php

namespace App\Services\AI;

interface AIServiceInterface
{
    /**
     * Generate a response from the AI provider.
     *
     * @param string $systemPrompt The system personality/context for the store
     * @param array $conversationHistory Last N messages from the conversation
     * @param string|null $productContext Additional product information
     * @return string The AI-generated response
     */
    public function generateResponse(
        string $systemPrompt,
        array $conversationHistory,
        ?string $productContext = null
    ): string;

    /**
     * Get the model identifier configured for this service.
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Test connectivity and authentication with the AI provider.
     *
     * @return bool
     */
    public function testConnection(): bool;
}
