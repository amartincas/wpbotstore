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
}
