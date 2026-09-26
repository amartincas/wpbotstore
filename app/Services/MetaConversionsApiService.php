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
        // Meta rejects "Lead" for action_source=business_messaging; the
        // supported event name for this channel is "LeadSubmitted".
        self::sendEvent($store, 'LeadSubmitted', $customerPhone, [], $ctwaClid);
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

        // Without ctwa_clid, Meta has nothing to attribute the event to (the
        // only other identifier, whatsapp_business_account_id, is the same for
        // every conversation of this store) — sending it anyway would just add
        // noise to the dataset with no ad-optimization value. This means
        // conversations that didn't start from a Click-to-WhatsApp ad don't
        // report events, which is expected: there's no ad to attribute to.
        if (!$ctwaClid) {
            Log::info('MetaConversionsApiService: event skipped, no ctwa_clid (conversation did not start from a Click-to-WhatsApp ad)', [
                'store_id' => $store->id,
                'event_name' => $eventName,
                'customer_phone' => $customerPhone,
            ]);
            return;
        }

        try {
            $url = "https://graph.facebook.com/" . self::API_VERSION . "/{$store->meta_dataset_id}/events";

            // Per Meta's Conversions API for Business Messaging spec, WhatsApp
            // events are matched via whatsapp_business_account_id + ctwa_clid,
            // not a hashed customer phone (that's only for the website Pixel/CAPI).
            $payload = [
                'data' => [[
                    'event_name' => $eventName,
                    'event_time' => now()->timestamp,
                    'action_source' => 'business_messaging',
                    'messaging_channel' => 'whatsapp',
                    'user_data' => [
                        'whatsapp_business_account_id' => $store->wa_business_account_id,
                        'ctwa_clid' => $ctwaClid,
                    ],
                    'custom_data' => $customData,
                ]],
            ];

            $response = Http::withToken($store->meta_capi_access_token)->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('MetaConversionsApiService: event send failed', [
                    'store_id' => $store->id,
                    'event_name' => $eventName,
                    'customer_phone' => $customerPhone,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return;
            }

            Log::info('MetaConversionsApiService: event sent', [
                'store_id' => $store->id,
                'event_name' => $eventName,
                'customer_phone' => $customerPhone,
                'response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error('MetaConversionsApiService: event send error', [
                'store_id' => $store->id,
                'event_name' => $eventName,
                'customer_phone' => $customerPhone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
