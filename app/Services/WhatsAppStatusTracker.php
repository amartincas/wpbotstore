<?php

namespace App\Services;

use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Log;

class WhatsAppStatusTracker
{
    /**
     * Store the WAMID (Meta's message ID) with initial "pending" status.
     * Called when a message is sent via WhatsAppService.
     *
     * Status lives directly on the whatsapp_messages row (not a cache entry
     * with a TTL) so it never silently reverts to "pending" once a cache
     * entry expires — the message's actual delivery history is permanent.
     *
     * @param int $dbMessageId - The database message ID
     * @param string $wamid - Meta's Webhook-Received-Message-ID (the unique message ID from Meta)
     */
    public static function trackMessage(int $dbMessageId, string $wamid): void
    {
        WhatsAppMessage::where('id', $dbMessageId)->update([
            'wamid' => $wamid,
            'status' => 'pending',
            'status_updated_at' => now(),
        ]);

        Log::debug('WhatsAppStatusTracker: Message tracked', [
            'db_message_id' => $dbMessageId,
            'wamid' => $wamid,
            'status' => 'pending',
        ]);
    }

    /**
     * Update message status when Meta sends webhook events.
     * Called from WhatsAppController when processing Meta status webhook.
     *
     * @param string $wamid - Meta's message ID from the webhook
     * @param string $status - One of: 'sent', 'delivered', 'read', 'failed'
     */
    public static function updateStatus(string $wamid, string $status): void
    {
        $updated = WhatsAppMessage::where('wamid', $wamid)->update([
            'status' => $status,
            'status_updated_at' => now(),
        ]);

        if (!$updated) {
            Log::warning('WhatsAppStatusTracker: No message found for wamid', [
                'wamid' => $wamid,
                'status' => $status,
            ]);
            return;
        }

        Log::debug('WhatsAppStatusTracker: Status updated', [
            'wamid' => $wamid,
            'new_status' => $status,
        ]);
    }

    /**
     * Get message status.
     * Called by Livewire component to fetch current status for display.
     *
     * @param int $dbMessageId - The database message ID
     * @return array|null {wamid, status, updated_at} or null if not found
     */
    public static function getStatus(int $dbMessageId): ?array
    {
        $message = WhatsAppMessage::find($dbMessageId);

        if (!$message || !$message->status) {
            return null;
        }

        return [
            'wamid' => $message->wamid,
            'status' => $message->status,
            'updated_at' => $message->status_updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get all statuses for multiple messages.
     * Called by Livewire to fetch all statuses for current conversation.
     *
     * @param array $dbMessageIds - Array of database message IDs
     * @return array - Key: db_message_id, Value: {wamid, status, updated_at}
     */
    public static function getMultipleStatuses(array $dbMessageIds): array
    {
        if (empty($dbMessageIds)) {
            return [];
        }

        return WhatsAppMessage::whereIn('id', $dbMessageIds)
            ->whereNotNull('status')
            ->get(['id', 'wamid', 'status', 'status_updated_at'])
            ->mapWithKeys(fn (WhatsAppMessage $message) => [
                $message->id => [
                    'wamid' => $message->wamid,
                    'status' => $message->status,
                    'updated_at' => $message->status_updated_at?->toIso8601String(),
                ],
            ])
            ->all();
    }
}
