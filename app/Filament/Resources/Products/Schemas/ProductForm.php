<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
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
                Group::make([
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
                ])->columnSpanFull(),
            ]);
    }
}
