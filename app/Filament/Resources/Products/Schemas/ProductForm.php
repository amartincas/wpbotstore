<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('store_id')
                    ->relationship(
                        'store',
                        'name',
                        fn ($query) => $query
                            ->when(
                                !Auth::user()?->is_super_admin,
                                fn ($q) => $q->where('id', Auth::user()?->store_id)
                            )
                    )
                    ->required()
                    ->default(Auth::user()?->store_id),
                TextInput::make('id')
                    ->label('Product ID (for AI image references)')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($record) => $record?->id ? "Use [IMG:{$record->id}] to show this product" : '(ID will be assigned after creation)')
                    ->helperText('Reference this ID in system prompts or AI responses to display product images')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                ToggleButtons::make('type')
                    ->options([
                        'product' => 'Product',
                        'service' => 'Service',
                    ])
                    ->default('product')
                    ->required()
                    ->inline(),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label(function (Get $get): string {
                        return $get('type') === 'service'
                            ? 'Availability (1 = Accepting Clients, 0 = Fully Booked)'
                            : 'Stock Quantity';
                    })
                    ->helperText(function (Get $get): string {
                        return $get('type') === 'service'
                            ? 'Enter 1 if accepting new clients, 0 if fully booked'
                            : 'Enter the number of units in stock';
                    }),
                Textarea::make('ai_sales_strategy')
                    ->label('AI Sales Strategy')
                    ->placeholder('How should the AI sell this product? E.g., "Emphasize quality over price" or "Focus on limited availability"...')
                    ->columnSpanFull(),
                Textarea::make('faq_context')
                    ->label('FAQ & Operational Context')
                    ->placeholder('Rules, cities served, employees, FAQs specific to this product...')
                    ->columnSpanFull(),
                TextInput::make('required_customer_info')
                    ->label('Required Lead Data')
                    ->placeholder('E.g., Full name, phone, delivery address, preferred date...')
                    ->columnSpanFull(),

                // === PRODUCT GALLERY ===
                \Filament\Schemas\Components\Text::make('existing_images')
                ->label('Current Images')
                ->content(function ($record) {
                    $images = $record?->images()->get() ?? collect();

                    if ($images->isEmpty()) {
                        return new \Illuminate\Support\HtmlString(
                            '<p style="color:#6b7280;font-size:14px;">No images yet.</p>'
                        );
                    }

                    $html = '<div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:8px;">';
                    foreach ($images as $image) {
                        $url = asset('storage/' . $image->image_path);
                        $html .= '
                            <div style="text-align:center;">
                                <img src="' . e($url) . '" 
                                    style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;display:block;">
                                <span style="font-size:12px;font-weight:600;color:#374151;margin-top:6px;display:block;">
                                    ID: ' . e($image->id) . '
                                </span>
                            </div>';
                    }
                    $html .= '</div>';

                    return new \Illuminate\Support\HtmlString($html);
                })
                ->columnSpanFull(),
            ]);
    }
}
