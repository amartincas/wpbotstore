<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends server-side conversion events to Meta's Conversions API (CAPI) for
 * WhatsApp Business Messaging, so ad campaigns get real lead/purchase
 * signals instead of relying only on click data.
 *
 * Every public method here swallows its own errors: a failure to reach Meta
 * must never block sending a message to the customer.
 */
class MetaConversionsApiService
{
    private const API_VERSION = 'v20.0';

    public static function sendLeadEvent(Store $store, string $customerPhone, ?string $ctwaClid = null): void
    {
        self::sendEvent($store, 'Lead', $customerPhone, [], $ctwaClid);
    }

    public static function sendPurchaseEvent(
        Store $store,
        string $customerPhone,
        float $value,
        ?string $currency,
        ?string $ctwaClid = null
    ): void {
        // No currency fallback on purpose: stores price in different currencies
        // (COP, USD, ...), so silently assuming one would report the wrong value
        // to Meta. If it's missing, skip the event instead of guessing.
        if (!$currency) {
            Log::warning('MetaConversionsApiService: Purchase event skipped, store has no meta_capi_currency configured', [
                'store_id' => $store->id,
            ]);
            return;
        }

        self::sendEvent($store, 'Purchase', $customerPhone, [
            'value' => $value,
            'currency' => $currency,
        ], $ctwaClid);
    }

    private static function sendEvent(
        Store $store,
        string $eventName,
        string $customerPhone,
        array $customData,
        ?string $ctwaClid
    ): void {
        if (!$store->hasCapiConfigured()) {
            Log::info('MetaConversionsApiService: skipped, store has no CAPI credentials', [
                'store_id' => $store->id,
                'event_name' => $eventName,
            ]);
            return;
        }

        try {
            $url = "https://graph.facebook.com/" . self::API_VERSION . "/{$store->meta_dataset_id}/events";

            $userData = [
                'phone' => [hash('sha256', self::normalizePhone($customerPhone))],
            ];

            if ($ctwaClid) {
                $userData['ctwa_clid'] = $ctwaClid;
            }

            $payload = [
                'data' => [[
                    'event_name' => $eventName,
                    'event_time' => now()->timestamp,
                    'action_source' => 'business_messaging',
                    'messaging_channel' => 'whatsapp',
                    'user_data' => $userData,
                    'custom_data' => $customData,
                ]],
            ];

            $response = Http::withToken($store->meta_capi_access_token)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('MetaConversionsApiService: event send failed', [
                    'store_id' => $store->id,
                    'event_name' => $eventName,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return;
            }

            Log::info('MetaConversionsApiService: event sent', [
                'store_id' => $store->id,
                'event_name' => $eventName,
                'has_ctwa_clid' => $ctwaClid !== null,
                'response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error('MetaConversionsApiService: event send error', [
                'store_id' => $store->id,
                'event_name' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}
