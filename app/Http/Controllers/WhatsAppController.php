<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Conversation;
use App\Jobs\ProcessWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * Handle Meta WhatsApp webhook verification challenge.
     * GET /api/whatsapp/webhook/{store_token}
     *
     * Meta sends a GET request with query parameters:
     * - hub.mode=subscribe
     * - hub.challenge=<challenge_string>
     * - hub.verify_token=<verify_token>
     *
     * @param Request $request
     * @param string $store_token
     * @return Response
     */
    public function verify(Request $request, string $store_token)
    {
        $store = Store::where('wa_verify_token', $store_token)->first();

        if (!$store) {
            return response('Store Not Found', 404);
        }

        // Capturamos los datos que envía Meta
        $mode = $request->input('hub_mode');
        $challenge = $request->input('hub_challenge');
        $verifyToken = $request->input('hub_verify_token');

        // Validamos contra el token de la base de datos
        if ($mode === 'subscribe' && $verifyToken === $store->wa_verify_token) {
            // IMPORTANTE: Retornar solo el challenge como texto plano
            return response($challenge, 200)
                    ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages from Meta webhook.
     * POST /api/whatsapp/webhook/{store_token}
     *
     * @param Request $request
     * @param string $store_token
     * @return Response
     */
    public function handle(Request $request, string $store_token): Response
    {
        // Find store by wa_verify_token
        $store = Store::where('wa_verify_token', $store_token)->first();

        if (!$store) {
            Log::warning('WhatsApp message handling failed: store not found', [
                'store_token' => $store_token,
            ]);
            return response('Not Found', 404);
        }

        // Always return 200 immediately to acknowledge receipt
        // Process messages asynchronously to avoid timeout issues

        $payload = $request->json()->all();

        Log::debug('WhatsApp webhook received', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'payload' => $payload,
        ]);

        // Check if this is a message event (not a status update)
        // Status updates don't have messages array
        if (!isset($payload['entry'][0]['changes'][0]['value']['messages'])) {
            Log::debug('WhatsApp webhook received but no messages array found (likely status event)', [
                'store_id' => $store->id,
            ]);
            return response('OK', 200);
        }

        // Extract first message from the array
        $messages = $payload['entry'][0]['changes'][0]['value']['messages'];
        if (empty($messages)) {
            Log::debug('WhatsApp messages array is empty', [
                'store_id' => $store->id,
            ]);
            return response('OK', 200);
        }

        $message = $messages[0];
        
        // Extract sender phone and message body
        $fromPhone = $message['from'] ?? null;
        $body = $message['text']['body'] ?? null;

        if (!$fromPhone || !$body) {
            Log::warning('WhatsApp message incomplete', [
                'store_id' => $store->id,
                'has_from' => isset($message['from']),
                'has_body' => isset($message['text']['body']),
            ]);
            return response('OK', 200);
        }

        // Log the actual message content clearly
        Log::info("CONTENIDO REAL: " . $body);

        // Log extracted message data
        Log::info('WhatsApp message received', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'customer_phone' => $fromPhone,
            'message_body' => $body,
            'message_id' => $message['id'] ?? null,
            'timestamp' => $message['timestamp'] ?? null,
        ]);

        // Save to conversations table - find or create conversation for this customer
        $conversation = Conversation::firstOrCreate(
            [
                'store_id' => $store->id,
                'customer_phone' => $fromPhone,
            ],
            [
                'last_session_at' => now(),
            ]
        );

        // Update last_session_at if conversation already existed
        if ($conversation->wasRecentlyCreated === false) {
            $conversation->update(['last_session_at' => now()]);
        }

        Log::debug('Conversation saved', [
            'store_id' => $store->id,
            'conversation_id' => $conversation->id,
            'customer_phone' => $fromPhone,
            'created' => $conversation->wasRecentlyCreated,
        ]);

        // Dispatch job to process the message asynchronously
        $phoneId = $message['id'] ?? null;
        ProcessWhatsAppMessage::dispatch($store, $fromPhone, $body, $phoneId);

        Log::info('WhatsApp message queued for processing', [
            'store_id' => $store->id,
            'conversation_id' => $conversation->id,
            'customer_phone' => $fromPhone,
            'message_id' => $phoneId,
        ]);

        return response('EVENT_RECEIVED', 200);
    }
}
