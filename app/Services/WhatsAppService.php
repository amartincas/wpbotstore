<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a message via WhatsApp Business API.
     *
     * @param string $to Phone number in format: countrycode[phonenumber]
     * @param string $message Message text to send
     * @param Store $store Store with WhatsApp credentials
     * @return bool True if message was sent successfully
     */
    public static function sendMessage(string $to, string $message, Store $store): bool
    {
        try {
            $url = "https://graph.facebook.com/v20.0/{$store->wa_phone_number_id}/messages";

            $response = Http::withToken($store->wa_access_token)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);
            
            if ($response->failed()) {
                Log::error("Error de Meta API", [
                    'store_id' => $store->id,
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
            }    

            Log::debug('WhatsApp message sent', [
                'store_id' => $store->id,
                'to' => $to,
                'status' => $response->status(),
                'success' => $response->successful(),
            ]);

            if (!$response->successful()) {
                Log::warning('WhatsApp message send failed', [
                    'store_id' => $store->id,
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp message send error', [
                'store_id' => $store->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Test WhatsApp connection with provided credentials
     *
     * @param string $phoneNumberId WhatsApp Phone Number ID
     * @param string $accessToken WhatsApp Business API access token
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public static function testConnection(string $phoneNumberId, string $accessToken): array
    {
        try {
            $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}";

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Connection successful! Phone number: ' . ($data['display_phone_number'] ?? 'Unknown'),
                    'data' => $data,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error from Meta API';

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $errorMessage,
                'data' => $errorData,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Send a welcome message after successful setup
     *
     * @param Store $store Store with WhatsApp credentials
     * @param string|null $toNumber Optional target phone number (if null, just logs)
     * @return bool True if message was sent or logged successfully
     */
    public static function sendWelcomeMessage(Store $store, ?string $toNumber = null): bool
    {
        try {
            $message = sprintf(
                "¡Hola! 🚀 Soy el asistente de %s. Estoy configurado correctamente y listo para atender a tus clientes. ¡Hagamos crecer tu negocio!",
                $store->name
            );

            // If target number is provided, send the message
            if ($toNumber) {
                return self::sendMessage($toNumber, $message, $store);
            }

            // Otherwise, just log it for the store owner to see
            Log::info('Store setup completed - Welcome message ready', [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'message' => $message,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error preparing welcome message', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send an image via WhatsApp Business API.
     *
     * @param string $toNumber Phone number in format: countrycode[phonenumber]
     * @param string $imageUrl Full URL to the image (public accessible)
     * @param Store $store Store with WhatsApp credentials
     * @param string|null $caption Optional caption for the image
     * @return bool True if image was sent successfully
     */
    public static function sendWhatsAppImage(
        string $toNumber,
        string $imageUrl,
        Store $store,
        ?string $caption = null
    ): bool {
        try {
            $url = "https://graph.facebook.com/v20.0/{$store->wa_phone_number_id}/messages";

            $imagePayload = [
                'link' => $imageUrl,
            ];

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $toNumber,
                'type' => 'image',
                'image' => $imagePayload,
            ];

            // Add caption if provided (appears as text above image)
            if ($caption) {
                $payload['image']['caption'] = $caption;
            }

            $response = Http::withToken($store->wa_access_token)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('WhatsApp image send failed', [
                    'store_id' => $store->id,
                    'to' => $toNumber,
                    'image_url' => $imageUrl,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return false;
            }

            Log::debug('WhatsApp image sent', [
                'store_id' => $store->id,
                'to' => $toNumber,
                'image_url' => $imageUrl,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp image send error', [
                'store_id' => $store->id,
                'to' => $toNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process AI response to extract image tags and send images.
     *
     * This function finds all [IMG: product_id] tags in the response,
     * sends the corresponding images via WhatsApp, and returns the cleaned text.
     *
     * @param string $responseText Raw AI response containing potential [IMG: id] tags
     * @param Store $store Store with WhatsApp credentials and products
     * @param string $customerNumber Customer's phone number to send images to
     * @return string Cleaned response text without [IMG: ...] tags
     */
    public static function processAIResponse(
        string $responseText,
        Store $store,
        string $customerNumber
    ): string {
        try {
            // Find all [IMG: id] tags
            $pattern = '/\[IMG:\s*(\d+)\s*\]/i';
            preg_match_all($pattern, $responseText, $matches);

            Log::info('AI Response Image Processing', [
                'store_id' => $store->id,
                'customer_number' => $customerNumber,
                'response_text' => $responseText,
                'found_img_tags' => $matches[1] ?? [],
            ]);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $productId) {
                    Log::info('Processing image tag', [
                        'store_id' => $store->id,
                        'product_id' => $productId,
                        'customer_number' => $customerNumber,
                    ]);

                    $product = $store->products()
                        ->where('id', $productId)
                        ->first();

                    if ($product) {
                        Log::info('Product found', [
                            'store_id' => $store->id,
                            'product_id' => $productId,
                            'product_name' => $product->name,
                        ]);

                        $image = $product->getPrimaryImage();
                        if ($image) {
                            Log::info('Image found, sending', [
                                'store_id' => $store->id,
                                'product_id' => $productId,
                                'image_path' => $image->image_path,
                                'public_url' => $image->public_url,
                                'customer_number' => $customerNumber,
                            ]);

                            // Send the image
                            $imageSent = self::sendWhatsAppImage(
                                $customerNumber,
                                $image->public_url,
                                $store,
                                $product->name
                            );

                            Log::info('Image send result', [
                                'store_id' => $store->id,
                                'product_id' => $productId,
                                'image_sent' => $imageSent,
                                'customer_number' => $customerNumber,
                            ]);
                        } else {
                            Log::warning('Product image not found for AI response', [
                                'store_id' => $store->id,
                                'product_id' => $productId,
                                'product_name' => $product->name,
                                'total_images' => $product->images()->count(),
                            ]);
                        }
                    } else {
                        Log::warning('Product not found for AI response', [
                            'store_id' => $store->id,
                            'product_id' => $productId,
                        ]);
                    }
                }
            } else {
                Log::info('No image tags found in AI response', [
                    'store_id' => $store->id,
                    'customer_number' => $customerNumber,
                ]);
            }

            // Remove all [IMG: ...] tags from response
            $cleanText = preg_replace($pattern, '', $responseText);

            // Clean up extra whitespace
            $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText));

            Log::info('AI Response processing completed', [
                'store_id' => $store->id,
                'customer_number' => $customerNumber,
                'original_length' => strlen($responseText),
                'cleaned_length' => strlen($cleanText),
            ]);

            return $cleanText;
        } catch (\Exception $e) {
            Log::error('Error processing AI response images', [
                'store_id' => $store->id,
                'customer_number' => $customerNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return original text if processing fails
            return $responseText;
        }
    }
}
