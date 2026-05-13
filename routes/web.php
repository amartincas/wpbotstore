<?php

use Illuminate\Support\Facades\Route;

// Filament handles the root path, authentication, and dashboard
// No need to define routes here - Filament will intercept and handle them

require __DIR__.'/settings.php';

Route::get('/test-image-send/{storeId}/{customerPhone}/{productId}', function ($storeId, $customerPhone, $productId) {
    try {
        $store = \App\Models\Store::findOrFail($storeId);
        $product = $store->products()->findOrFail($productId);

        // Get the primary image
        $image = $product->getPrimaryImage();

        if (!$image) {
            return response()->json([
                'success' => false,
                'error' => 'No image found for product ID: ' . $productId,
            ], 404);
        }

        // Send the image
        $success = \App\Services\WhatsAppService::sendWhatsAppImage(
            $customerPhone,
            $image->public_url,
            $store,
            $product->name
        );

        return response()->json([
            'success' => $success,
            'product' => $product->name,
            'image_url' => $image->public_url,
            'customer_phone' => $customerPhone,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/test-ai-response-processing/{storeId}/{customerPhone}', function ($storeId, $customerPhone) {
    try {
        $store = \App\Models\Store::findOrFail($storeId);

        // Simulate AI response with image tags
        $aiResponse = "Hello! Here are some products you might like:

This is a great product! [IMG:1]

And here's another one: [IMG:2]

Let me know if you need more information!";

        // Process the response (this will send images and clean the text)
        $cleanedMessage = \App\Services\WhatsAppService::processAIResponse(
            $aiResponse,
            $store,
            $customerPhone
        );

        // Send the cleaned text message
        $textSent = \App\Services\WhatsAppService::sendMessage($customerPhone, $cleanedMessage, $store);

        return response()->json([
            'success' => $textSent,
            'original_response' => $aiResponse,
            'cleaned_message' => $cleanedMessage,
            'customer_phone' => $customerPhone,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/debug-products/{storeId}', function ($storeId) {
    try {
        $store = \App\Models\Store::findOrFail($storeId);
        $products = $store->products()->with('images')->get();

        $debug = [];
        foreach ($products as $product) {
            $primaryImage = $product->getPrimaryImage();
            $debug[] = [
                'id' => $product->id,
                'name' => $product->name,
                'image_count' => $product->images->count(),
                'primary_image_path' => $primaryImage ? $primaryImage->image_path : null,
                'primary_image_url' => $primaryImage ? $primaryImage->public_url : null,
                'all_images' => $product->images->map(function ($img) {
                    return [
                        'path' => $img->image_path,
                        'url' => $img->public_url,
                        'is_primary' => $img->is_primary,
                    ];
                })->toArray(),
            ];
        }

        return response()->json([
            'store_id' => $storeId,
            'store_name' => $store->name,
            'products' => $debug,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/test-user-response/{storeId}/{customerPhone}', function ($storeId, $customerPhone) {
    try {
        $store = \App\Models\Store::findOrFail($storeId);

        // Simulate the exact AI response the user mentioned
        $aiResponse = "Placas de reparación de acero inoxidable diseñadas para fijar y reforzar bisagras de gabinetes, puertas y cajones cuyos tornillos se han soltado o cuya madera está dañada. Es la solución definitiva para muebles de cocina y armarios con madera vencida. 
Contenido del paquete: 4 placas de reparación de acero inoxidable + tornillos de fijación incluidos. [IMG:1]";

        // Process the response (this will send images and clean the text)
        $cleanedMessage = \App\Services\WhatsAppService::processAIResponse(
            $aiResponse,
            $store,
            $customerPhone
        );

        // Send the cleaned text message
        $textSent = \App\Services\WhatsAppService::sendMessage($customerPhone, $cleanedMessage, $store);

        return response()->json([
            'success' => $textSent,
            'original_response' => $aiResponse,
            'cleaned_message' => $cleanedMessage,
            'customer_phone' => $customerPhone,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});
