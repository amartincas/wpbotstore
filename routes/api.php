<?php

use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// WhatsApp webhook routes
// GET: Para que Meta valide la conexión de una tienda específica
Route::get('/whatsapp/webhook/{store_token}', [WhatsAppController::class, 'verify'])
    ->name('whatsapp.verify');

// POST: Para recibir los mensajes de esa tienda específica
Route::post('/whatsapp/webhook/{store_token}', [WhatsAppController::class, 'handle'])
    ->name('whatsapp.handle');


