<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductImage;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Handle image uploads
        $this->handleImageUploads();
    }

    protected function handleImageUploads(): void
    {
        $uploadedFiles = $this->data['images'] ?? [];

        if (empty($uploadedFiles)) {
            return;
        }

        // Get existing images
        $existingImages = $this->record->images()->pluck('image_path')->toArray();

        // Find new files (those not in existing images)
        $newFiles = array_diff($uploadedFiles, $existingImages);

        // Create ProductImage records for new files
        foreach ($newFiles as $index => $filePath) {
            ProductImage::create([
                'product_id' => $this->record->id,
                'image_path' => $filePath,
                'is_primary' => $index === 0 && empty($existingImages), // First image is primary if no existing images
            ]);
        }

        // Remove images that were deleted
        $deletedImages = array_diff($existingImages, $uploadedFiles);
        if (!empty($deletedImages)) {
            ProductImage::whereIn('image_path', $deletedImages)
                ->where('product_id', $this->record->id)
                ->delete();

            // Also delete files from storage
            foreach ($deletedImages as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }
    }
}
