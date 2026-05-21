<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Conversation;
use App\Jobs\ProcessWhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        // Find store by decrypted wa_verify_token
        // Since wa_verify_token is encrypted in DB, we load all stores and compare
        // (Store table is small, so this is efficient)
        $store = Store::all()->firstWhere('wa_verify_token', $store_token);

        if (!$store) {
            Log::warning('WhatsApp webhook verification failed: store not found', [
                'token_length' => strlen($store_token),
            ]);
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
    $payload = $request->json()->all();

    // 1. FILTRAR EVENTOS DE ESTADO (Sent, Delivered, Read)
    if (isset($payload['entry'][0]['changes'][0]['value']['statuses'])) {
        return response('OK', 200);
    }

    // Extract first message from the array
    $messages = $payload['entry'][0]['changes'][0]['value']['messages'] ?? [];
    if (empty($messages)) {
        return response('OK', 200);
    }

    $message = $messages[0];
    $phoneId = $message['id'] ?? null; // Este es el WAMID único de Meta

    // 🔥 CONTROL DE IDEMPOTENCIA: Bloquear reintentos de Meta de inmediato
    if ($phoneId) {
        $cacheKey = "whatsapp_msg_processed:{$phoneId}";
        
        // Si el ID ya existe en la caché, es un reintento. Respondemos 200 y salimos.
        if (Cache::has($cacheKey)) {
            Log::warning('WhatsApp Webhook: Reintento de Meta detectado e ignorado.', ['message_id' => $phoneId]);
            return response('EVENT_RECEIVED', 200);
        }
        
        // Guardamos el ID en caché por 10 minutos para evitar duplicados
        Cache::put($cacheKey, true, now()->addMinutes(10));
    }

    // Si pasa los filtros, guardamos el log real del mensaje entrante
    Log::info('Raw WhatsApp Webhook Payload', ['payload' => $payload]);

    $store = $this->resolveStoreFromPayload($payload);
    if (!$store) {
        Log::warning('WhatsApp message handling failed: unable to resolve store from webhook metadata', [
            'store_token' => $store_token,
            'payload_metadata' => data_get($payload, 'entry.0.changes.0.value.metadata'),
        ]);
        return response('Not Found', 404);
    }

    $type = $message['type'] ?? null;
    $fromPhone = $message['from'] ?? null;

    $body = null;
    $mediaId = null;

    if ($type === 'text') {
        $body = $message['text']['body'] ?? null;
    } elseif (in_array($type, ['audio', 'voice'], true)) {
        $mediaId = $message[$type]['id'] ?? null;
        
        // 💡 Si es audio, ponle un placeholder temporal al body para evitar romper la lógica aguas abajo
        $body = "[Mensaje de Voz/Audio]"; 
        
        Log::info('WhatsApp audio/voice message received', [
            'store_id' => $store->id,
            'customer_phone' => $fromPhone,
            'message_id' => $phoneId,
            'media_id' => $mediaId,
        ]);
    }

    if (!$fromPhone || (!$body && !$mediaId)) {
        return response('OK', 200);
    }

    Log::info("CONTENIDO REAL: " . $body);

    // Find or create conversation
    $conversation = Conversation::firstOrCreate(
        ['store_id' => $store->id, 'customer_phone' => $fromPhone],
        ['last_session_at' => now()]
    );

    if ($conversation->wasRecentlyCreated === false) {
        $conversation->update(['last_session_at' => now()]);
    }

    // Dispatch job to process the message asynchronously
    ProcessWhatsAppMessage::dispatch(
        $store,
        $fromPhone,
        $body,
        $phoneId,
        $type,
        $mediaId
    );

    Log::info('WhatsApp message queued for processing', [
        'store_id' => $store->id,
        'message_id' => $phoneId,
    ]);

    return response('EVENT_RECEIVED', 200);
}

    private function resolveStoreFromPayload(array $payload): ?Store
    {
        $metadata = data_get($payload, 'entry.0.changes.0.value.metadata', []);
        $phoneNumberId = $metadata['phone_number_id'] ?? $metadata['phoneNumberId'] ?? null;

        if (empty($phoneNumberId)) {
            return null;
        }

        return Store::where('wa_phone_number_id', (string) $phoneNumberId)->first();
    }
}
