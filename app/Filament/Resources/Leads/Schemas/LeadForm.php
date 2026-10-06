<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Regular users get their store_id from CreateLead's
                // mutateFormDataBeforeCreate(); super admins have no store of
                // their own, so they need to pick one explicitly here.
                Select::make('store_id')
                    ->label('Store')
                    ->relationship('store', 'name')
                    ->required()
                    ->visible(Auth::user()?->is_super_admin)
                    ->columnSpanFull(),
                TextInput::make('customer_name')
                    ->label('Full Name')
                    ->required()
                    ->columnSpan(2),
                TextInput::make('customer_phone')
                    ->label('WhatsApp Number')
                    ->tel()
                    ->required()
                    ->columnSpan(2),
                Textarea::make('delivery_address_or_location')
                    ->label('Delivery Address / Service Location')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('product_service_name')
                    ->label('Product / Service Name')
                    ->columnSpan(2),
                TextInput::make('sale_value')
                    ->label('Sale Value')
                    ->numeric()
                    ->helperText('Se resuelve solo del catálogo si coincide con "Product / Service Name" — ajústalo aquí si es distinto (precio negociado, varios productos, etc.).')
                    ->columnSpan(2),
                TextInput::make('preferred_date_time')
                    ->label('Preferred Date & Time')
                    ->hint('For services: preferred booking date/time')
                    ->columnSpan(2),
                Textarea::make('summary')
                    ->label('Order Summary')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
                Select::make('order_status')
                    ->label('Order Status')
                    ->options(Lead::ORDER_STATUSES)
                    ->helperText('Confirmado/Enviado se marcan solos; marca Entregado o Devuelto manualmente cuando corresponda.')
                    ->columnSpan(2),
                TextInput::make('tracking_number')
                    ->label('Tracking Number')
                    ->columnSpan(1),
                TextInput::make('carrier')
                    ->label('Carrier')
                    ->columnSpan(1),
                Toggle::make('needs_address_review')
                    ->label('Needs Address Review')
                    ->helperText('Flagged automatically when the delivery address seems to be missing a city/region.')
                    ->columnSpanFull(),
                Toggle::make('is_processed')
                    ->label('Mark as Processed')
                    ->hint('Toggle when the lead has been successfully handled')
                    ->columnSpanFull(),
                Toggle::make('bot_active')
                    ->label('Bot Active for this Client')
                    ->helperText('Disable this to handle the conversation manually.')
                    ->default(true)
                    ->columnSpanFull(),
                Placeholder::make('recent_conversation')
                    ->label('Recent Conversation')
                    ->content(function ($record) {
                        if (!$record) {
                            return view('filament.components.chat-history', [
                                'record' => null,
                                'messages' => collect([]),
                            ]);
                        }

                        $messages = \App\Models\WhatsAppMessage::where('store_id', $record->store_id)
                            ->where('customer_phone', $record->customer_phone)
                            ->orderBy('created_at', 'desc')
                            ->limit(15)
                            ->get()
                            ->reverse();

                        return view('filament.components.chat-history', [
                            'record' => $record,
                            'messages' => $messages,
                        ]);
                    })
                    ->columnSpanFull(),
            ]);
    }
}
