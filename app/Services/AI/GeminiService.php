<?php

namespace App\Services\AI;

use App\Contracts\AiServiceInterface;
use Illuminate\Support\Facades\Http;

class GeminiService implements AiServiceInterface
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Get a response from Google Gemini API.
     *
     * @param string $userMessage The user's message
     * @param string $systemPrompt The system prompt/instructions
     * @param array $history Chat history array of ['role' => 'user|assistant', 'content' => 'message']
     * @return string The AI response message content
     */
    public function getResponse(string $userMessage, string $systemPrompt, array $history = []): string
    {
        try {
            // Build contents array: history + current message
            // Gemini uses "contents" instead of "messages"
            $contents = [];

            // Add chat history
            foreach ($history as $msg) {
                $contents[] = [
                    'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [
                        [
                            'text' => $msg['content'],
                        ],
                    ],
                ];
            }

            // Add current user message
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $userMessage,
                    ],
                ],
            ];

            // Build the request payload
            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                ],
            ];

            // Add system instruction if provided
            if (!empty($systemPrompt)) {
                $payload['systemInstruction'] = [
                    'parts' => [
                        [
                            'text' => $systemPrompt,
                        ],
                    ],
                ];
            }

            $response = Http::post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent",
                $payload,
                [
                    'x-goog-api-key' => $this->apiKey,
                ]
            );

            if ($response->failed()) {
                $errorMessage = $response->json()['error']['message'] ?? $response->body();
                throw new \Exception('Gemini API error: ' . $errorMessage);
            }

            $data = $response->json();

            // Extract content from Gemini response format
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        } catch (\Exception $e) {
            throw new \Exception('Gemini service error: ' . $e->getMessage());
        }
    }

    /**
     * Test Google Gemini API connectivity.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        if (!$this->apiKey) {
            return false;
        }

        try {
            $response = Http::post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => 'test',
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 10,
                    ],
                ],
                [
                    'x-goog-api-key' => $this->apiKey,
                ]
            );

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
