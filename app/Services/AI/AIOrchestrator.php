<?php

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\Product;
use App\Models\Store;
use App\Contracts\AiServiceInterface;
use App\Services\Ai\AiServiceFactory;
use Illuminate\Support\Facades\Cache;

class AIOrchestrator
{
    private AIServiceInterface $aiService;
    private Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->aiService = AIServiceFactory::make($store);
    }

    /**
     * Generate a response for a customer query.
     *
     * @param Conversation $conversation
     * @param string $customerMessage
     * @param Product|null $product
     * @return string
     */
    public function generateResponse(
        Conversation $conversation,
        string $customerMessage,
        ?Product $product = null
    ): string {
        $systemPrompt = $this->buildSystemPrompt();
        $conversationHistory = $this->getConversationHistory($conversation, $customerMessage);

        return $this->aiService->getResponse(
            $customerMessage,
            $systemPrompt,
            $conversationHistory
        );
    }

    /**
     * Build the system prompt from store personality and instructions.
     *
     * @return string
     */
    private function buildSystemPrompt(): string
    {
        $personalityGuidance = $this->getPersonalityGuidance($this->store->personality_type);
        $visualCatalog = $this->buildVisualCatalog();

        $catalogSection = $visualCatalog ? "\n\n## Your Product Catalog\n\n{$visualCatalog}" : '';

        return <<<PROMPT
You are a {$this->store->personality_type} assistant for {$this->store->name}.

{$this->store->system_prompt}

{$personalityGuidance}{$catalogSection}

You are responsive, friendly, and professional. Always prioritize the customer's needs.
PROMPT;
    }

    /**
     * Build the visual catalog section with product references.
     *
     * @return string
     */
    private function buildVisualCatalog(): string
    {
        $products = $this->store->products()->get();

        if ($products->isEmpty()) {
            return '';
        }

        $catalog = "To show customers specific products, use these references:\n\n";

        foreach ($products as $product) {
            $catalog .= sprintf(
                "- **%s** (ID: %d): %s. To show this product, use [IMG:%d]\n",
                $product->name,
                $product->id,
                $product->ai_sales_strategy ?? 'Premium offering',
                $product->id
            );
        }

        return trim($catalog);
    }

    /**
     * Get personality-specific guidance.
     *
     * @param string $personalityType
     * @return string
     */
    private function getPersonalityGuidance(string $personalityType): string
    {
        return match ($personalityType) {
            'vendedor' => 'Your role is to help customers find products, explain features, and close sales. Be persuasive and highlight product benefits.',
            'soporte' => 'Your role is to provide technical support and resolve customer issues. Be empathetic, patient, and solution-focused.',
            'asesor' => 'Your role is to advise customers on the best solutions for their needs. Be knowledgeable, thoughtful, and advisory.',
            default => 'Be helpful and professional.',
        };
    }

    /**
     * Get the last 5 messages from the conversation (from cache or DB).
     *
     * @param Conversation $conversation
     * @param string $currentMessage
     * @return array
     */
    private function getConversationHistory(Conversation $conversation, string $currentMessage): array
    {
        $cacheKey = "conversation:{$conversation->id}:history";

        // Retrieve last 5 messages from cache
        $history = Cache::get($cacheKey, []);

        // Ensure we have the right format (role/content pairs)
        if (!is_array($history) || empty($history)) {
            $history = [];
        }

        // Add the current customer message
        $history[] = ['role' => 'user', 'content' => $currentMessage];

        // Keep only the last 5 messages to avoid context bloat
        $history = array_slice($history, -5);

        return $history;
    }

    /**
     * Build product context string if a product is provided.
     *
     * @param Product|null $product
     * @return string|null
     */
    private function buildProductContext(?Product $product): ?string
    {
        if (!$product) {
            return null;
        }

        return <<<CONTEXT
Product: {$product->name}
Price: \${$product->price}
Stock: {$product->stock} units available
Description: {$product->description}
CONTEXT;
    }

    /**
     * Cache the assistant's response in the conversation history.
     *
     * @param Conversation $conversation
     * @param string $response
     * @return void
     */
    public function cacheResponse(Conversation $conversation, string $response): void
    {
        $cacheKey = "conversation:{$conversation->id}:history";
        $history = Cache::get($cacheKey, []);

        // Add the assistant's response
        $history[] = ['role' => 'assistant', 'content' => $response];

        // Keep only the last 5 messages
        $history = array_slice($history, -5);

        // Cache for 24 hours
        Cache::put($cacheKey, $history, now()->addHours(24));

        // Update conversation's last_session_at
        $conversation->update(['last_session_at' => now()]);
    }

    /**
     * Get the underlying AI service.
     *
     * @return AIServiceInterface
     */
    public function getAIService(): AIServiceInterface
    {
        return $this->aiService;
    }
}
