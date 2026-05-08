<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductImage;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        // Handle image uploads
        $uploadedFiles = $this->data['images'] ?? [];

        if (empty($uploadedFiles)) {
            return;
        }

        // Create ProductImage records
        foreach ($uploadedFiles as $index => $filePath) {
            ProductImage::create([
                'product_id' => $this->record->id,
                'image_path' => $filePath,
                'is_primary' => $index === 0, // First image is primary
            ]);
        }
    }
}
