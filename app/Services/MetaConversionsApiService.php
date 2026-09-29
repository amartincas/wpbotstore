<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends server-side conversion events to Meta's Conversions API (CAPI).
 *
 * Two attribution paths are supported, both against the same dataset:
 * - business_messaging (ctwa_clid): for leads/purchases from a real-time
 *   WhatsApp conversation that started from a Click-to-WhatsApp ad.
 * - physical_store (hashed phone): for leads/purchases that came from an ad
 *   but never had a ctwa_clid captured (e.g. leads created before this
 *   integration existed) — Meta tries to match the person by phone number
 *   against its own user base instead of a click id. This is what used to
 *   be the separate "Offline Conversions API", since folded into the
 *   regular Conversions API.
 *
 * Every public method here swallows its own errors: a failure to reach Meta
 * must never block sending a message to the customer.
 */
class MetaConversionsApiService
{
    private const API_VERSION = 'v20.0';

    public static function sendLeadEvent(
        Store $store,
        string $customerPhone,
        ?string $ctwaClid = null,
        ?\DateTimeInterface $eventTime = null
    ): void {
        // Meta rejects "Lead" for action_source=business_messaging; the
        // supported event name for this channel is "LeadSubmitted".
        self::sendBusinessMessagingEvent($store, 'LeadSubmitted', $customerPhone, [], $ctwaClid, $eventTime);
    }

    public static function sendPurchaseEvent(
        Store $store,
        string $customerPhone,
        float $value,
        ?string $currency,
        ?string $ctwaClid = null,
        ?\DateTimeInterface $eventTime = null
    ): void {
        $customData = self::purchaseCustomData($store, $value, $currency);
        if ($customData === null) {
            return;
        }

        self::sendBusinessMessagingEvent($store, 'Purchase', $customerPhone, $customData, $ctwaClid, $eventTime);
    }

    /**
     * Backfill path for leads that came from an ad but never had a
     * ctwa_clid captured. Uses "Lead" as event_name — unlike
     * business_messaging, physical_store events don't require the special
     * "LeadSubmitted" name.
     */
    public static function sendOfflineLeadEvent(
        Store $store,
        string $customerPhone,
        \DateTimeInterface $eventTime
    ): void {
        self::sendPhysicalStoreEvent($store, 'Lead', $customerPhone, [], $eventTime);
    }

    public static function sendOfflinePurchaseEvent(
        Store $store,
        string $customerPhone,
        float $value,
        ?string $currency,
        \DateTimeInterface $eventTime
    ): void {
        $customData = self::purchaseCustomData($store, $value, $currency);
        if ($customData === null) {
            return;
        }

        self::sendPhysicalStoreEvent($store, 'Purchase', $customerPhone, $customData, $eventTime);
    }

    private static function purchaseCustomData(Store $store, float $value, ?string $currency): ?array
    {
        // No currency fallback on purpose: stores price in different currencies
        // (COP, USD, ...), so silently assuming one would report the wrong value
        // to Meta. If it's missing, skip the event instead of guessing.
        if (!$currency) {
            Log::warning('MetaConversionsApiService: Purchase event skipped, store has no meta_capi_currency configured', [
                'store_id' => $store->id,
            ]);
            return null;
        }

        return ['value' => $value, 'currency' => $currency];
    }

    private static function sendBusinessMessagingEvent(
        Store $store,
        string $eventName,
        string $customerPhone,
        array $customData,
        ?string $ctwaClid,
        ?\DateTimeInterface $eventTime
    ): void {
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

        // Per Meta's Conversions API for Business Messaging spec, WhatsApp
        // events are matched via whatsapp_business_account_id + ctwa_clid,
        // not a hashed customer phone (that's only for the offline/website path).
        self::send($store, [
            'event_name' => $eventName,
            'event_time' => ($eventTime ?? now())->getTimestamp(),
            'action_source' => 'business_messaging',
            'messaging_channel' => 'whatsapp',
            'user_data' => [
                'whatsapp_business_account_id' => $store->wa_business_account_id,
                'ctwa_clid' => $ctwaClid,
            ],
            'custom_data' => $customData,
        ], $customerPhone);
    }

    private static function sendPhysicalStoreEvent(
        Store $store,
        string $eventName,
        string $customerPhone,
        array $customData,
        \DateTimeInterface $eventTime
    ): void {
        self::send($store, [
            'event_name' => $eventName,
            'event_time' => $eventTime->getTimestamp(),
            'action_source' => 'physical_store',
            'user_data' => [
                'ph' => [hash('sha256', self::normalizePhone($customerPhone))],
            ],
            'custom_data' => $customData,
        ], $customerPhone);
    }

    private static function send(Store $store, array $eventData, string $customerPhone): void
    {
        if (!$store->hasCapiConfigured()) {
            Log::info('MetaConversionsApiService: skipped, store has no CAPI credentials', [
                'store_id' => $store->id,
                'event_name' => $eventData['event_name'],
            ]);
            return;
        }

        try {
            $url = "https://graph.facebook.com/" . self::API_VERSION . "/{$store->meta_dataset_id}/events";

            $response = Http::withToken($store->meta_capi_access_token)->post($url, [
                'data' => [$eventData],
            ]);

            if (!$response->successful()) {
                Log::warning('MetaConversionsApiService: event send failed', [
                    'store_id' => $store->id,
                    'event_name' => $eventData['event_name'],
                    'action_source' => $eventData['action_source'],
                    'customer_phone' => $customerPhone,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return;
            }

            Log::info('MetaConversionsApiService: event sent', [
                'store_id' => $store->id,
                'event_name' => $eventData['event_name'],
                'action_source' => $eventData['action_source'],
                'customer_phone' => $customerPhone,
                'response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error('MetaConversionsApiService: event send error', [
                'store_id' => $store->id,
                'event_name' => $eventData['event_name'],
                'customer_phone' => $customerPhone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}
