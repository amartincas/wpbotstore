<?php

namespace App\Services\AI;

use App\Contracts\AiServiceInterface;
use Illuminate\Support\Facades\Http;

class OpenAiService implements AiServiceInterface
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Get a response from OpenAI API.
     *
     * @param string $userMessage The user's message
     * @param string $systemPrompt The system prompt/instructions
     * @param array $history Chat history array of ['role' => 'user|assistant', 'content' => 'message']
     * @return string The AI response message content
     */
    public function getResponse(string $userMessage, string $systemPrompt, array $history = []): string
    {
        try {
            // Build messages array: system prompt + history + current message
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
            ];

            // Add chat history
            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            // Add current user message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage,
            ];

            $response = Http::withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();

            return $data['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            throw new \Exception('OpenAI service error: ' . $e->getMessage());
        }
    }

    /**
     * Test OpenAI API connectivity.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        if (!$this->apiKey) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get('https://api.openai.com/v1/models');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
