<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppStatusTracker
{
    /**
     * Cache key prefix for message status tracking
     * Format: whatsapp_msg_status:{db_message_id}
     */
    private const CACHE_PREFIX = 'whatsapp_msg_status:';
    
    /**
     * Cache key prefix for reverse WAMID lookup
     * Format: whatsapp_wamid_lookup:{wamid}
     */
    private const WAMID_PREFIX = 'whatsapp_wamid_lookup:';
    
    /**
     * Cache TTL: 24 hours (in seconds)
     */
    private const CACHE_TTL = 86400;

    /**
     * Store the WAMID (Meta's message ID) with initial "pending" status
     * Called when a message is sent via WhatsAppService
     * 
     * @param int $dbMessageId - The database message ID
     * @param string $wamid - Meta's Webhook-Received-Message-ID (the unique message ID from Meta)
     */
    public static function trackMessage(int $dbMessageId, string $wamid): void
    {
        $cacheKey = self::CACHE_PREFIX . $dbMessageId;
        $wamidKey = self::WAMID_PREFIX . $wamid;
        
        $data = [
            'wamid' => $wamid,
            'status' => 'pending',
            'updated_at' => now()->toIso8601String(),
        ];
        
        // Store forward mapping: db_message_id → status data
        Cache::put($cacheKey, $data, now()->addSeconds(self::CACHE_TTL));
        
        // Store reverse mapping: wamid → db_message_id (for quick lookup from webhook)
        Cache::put($wamidKey, $dbMessageId, now()->addSeconds(self::CACHE_TTL));
        
        Log::debug('WhatsAppStatusTracker: Message tracked', [
            'db_message_id' => $dbMessageId,
            'wamid' => $wamid,
            'status' => 'pending',
        ]);
    }

    /**
     * Update message status when Meta sends webhook events
     * Called from WhatsAppController when processing Meta status webhook
     * 
     * @param string $wamid - Meta's message ID from the webhook
     * @param string $status - One of: 'sent', 'delivered', 'read', 'failed'
     */
    public static function updateStatus(string $wamid, string $status): void
    {
        // Use reverse lookup to find the db_message_id
        $wamidKey = self::WAMID_PREFIX . $wamid;
        $dbMessageId = Cache::get($wamidKey);
        
        if (!$dbMessageId) {
            Log::warning('WhatsAppStatusTracker: WAMID not found in reverse lookup cache', [
                'wamid' => $wamid,
                'status' => $status,
            ]);
            return;
        }
        
        // Update the forward mapping
        $cacheKey = self::CACHE_PREFIX . $dbMessageId;
        $data = Cache::get($cacheKey);
        
        if (!$data) {
            Log::warning('WhatsAppStatusTracker: Message data not found in forward cache', [
                'db_message_id' => $dbMessageId,
                'wamid' => $wamid,
                'status' => $status,
            ]);
            return;
        }
        
        $data['status'] = $status;
        $data['updated_at'] = now()->toIso8601String();
        
        Cache::put($cacheKey, $data, now()->addSeconds(self::CACHE_TTL));
        
        Log::debug('WhatsAppStatusTracker: Status updated', [
            'db_message_id' => $dbMessageId,
            'wamid' => $wamid,
            'new_status' => $status,
        ]);
    }

    /**
     * Get message status
     * Called by Livewire component to fetch current status for display
     * 
     * @param int $dbMessageId - The database message ID
     * @return array|null {wamid, status, updated_at} or null if not found
     */
    public static function getStatus(int $dbMessageId): ?array
    {
        $cacheKey = self::CACHE_PREFIX . $dbMessageId;
        return Cache::get($cacheKey);
    }

    /**
     * Get all statuses for multiple messages
     * Called by Livewire to fetch all statuses for current conversation
     * 
     * @param array $dbMessageIds - Array of database message IDs
     * @return array - Key: db_message_id, Value: {wamid, status, updated_at}
     */
    public static function getMultipleStatuses(array $dbMessageIds): array
    {
        $statuses = [];
        foreach ($dbMessageIds as $id) {
            $status = self::getStatus($id);
            if ($status) {
                $statuses[$id] = $status;
            }
        }
        return $statuses;
    }

    /**
     * Clear stale cache entries manually (optional cleanup)
     */
    public static function clearStale(): void
    {
        // Cache handles TTL automatically, this is just for manual cleanup if needed
        Log::info('WhatsAppStatusTracker: Cache cleanup completed');
    }
}
