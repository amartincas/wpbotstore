<div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 0; border: 1px solid #e5e7eb; height: 80vh; overflow: hidden; border-radius: 12px; background: white;">
    
    <div wire:poll.10s style="grid-column: span 4 / span 4; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; background: #f9fafb;">
        <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: white;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">Chats</h2>
            
            {{-- Store filter dropdown for superusers --}}
            @if(Auth::user()->is_super_admin)
                <select wire:model.live="filterStoreId" 
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; color: #111827;">
                    <option value="">All Stores</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div style="flex: 1; overflow-y: auto;">
            <div style="display: flex; flex-direction: column;">
                @foreach($conversations as $conversation)
                    {{-- Fetch store name for this conversation (if superuser) --}}
                    @php
                        $storeName = null;
                        if(Auth::user()->is_super_admin) {
                            $firstMsg = \App\Models\WhatsAppMessage::where('customer_phone', $conversation->customer_phone)->first();
                            if($firstMsg) {
                                $storeName = $firstMsg->store?->name;
                            }
                        }
                    @endphp
                    
                    <button wire:click="selectConversation('{{ $conversation->customer_phone }}')"
                        style="width: 100%; text-align: left; padding: 1rem; border-bottom: 1px solid #f3f4f6; background: {{ $selectedPhone === $conversation->customer_phone ? '#eff6ff' : 'white' }}; border-left: {{ $selectedPhone === $conversation->customer_phone ? '4px solid #2563eb' : 'none' }};">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $conversation->customer_phone }}</span>
                        {{-- Show store name if superuser --}}
                        @if($storeName)
                            <br><span style="font-size: 0.75rem; color: #6b7280;">{{ $storeName }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div style="grid-column: span 8 / span 8; display: flex; flex-direction: column; background: #e5ddd5; position: relative; height: 100%; overflow: hidden;">
        @if ($selectedPhone)
            <!-- Contenedor de la derecha (Mensajes) -->
            <div style="display: flex; flex-direction: column; height: 100%; min-height: 500px;">
                
                <!-- Cabecera -->
                <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: white; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-weight: 700; color: #111827; margin: 0;">{{ $selectedPhone }}</h3>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 12px; font-weight: 700; color: #4b5563;">BOT</span>
                            <input type="checkbox" wire:model.live="botActive" style="cursor: pointer; width: 16px; height: 16px;">
                        </div>
                    </div>
                </div>

                <!-- CONTENEDOR DE SCROLL (Aquí es donde estaba el error) -->
                <div id="chat-container" 
                    wire:poll.3s 
                    style="flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; background-color: #e5ddd5; position: relative;">
                    
                    @foreach ($messages as $message)
                        <div style="display: flex; width: 100%; justify-content: {{ $message->role === 'user' ? 'flex-start' : 'flex-end' }};">
                            <div style="max-width: 75%; padding: 0.6rem 1rem; border-radius: 12px; background: {{ $message->role === 'user' ? 'white' : '#dcf8c6' }}; box-shadow: 0 1px 1px rgba(0,0,0,0.1);">
                                @if(str_starts_with($message->content, '🎤 [AUDIO]:'))
                                    <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                                        <span style="background: #e1f5fe; color: #25D366; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">
                                            Nota de Voz
                                        </span>
                                    </div>
                                    <p style="font-size: 14px; margin: 0; color: #111827; font-style: italic;">
                                        {{ str_replace('🎤 [AUDIO]:', '', $message->content) }}
                                    </p>
                                @else
                                    <p style="font-size: 14px; margin: 0; word-wrap: break-word; white-space: pre-wrap; color: #111827;">{{ $message->content }}</p>
                                @endif
                                <div style="font-size: 10px; color: #6b7280; text-align: right; margin-top: 4px;">{{ $message->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                    <div id="messages-end"></div>
                </div>

                <!-- Área de Input (Abajo) -->
                <div style="padding: 1rem; background: #f0f0f0; border-top: 1px solid #e5e7eb; flex-shrink: 0;">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" wire:model="newMessage" wire:keydown.enter="sendMessage" 
                            placeholder="Escribe un mensaje..." 
                            style="flex: 1; padding: 0.6rem; border-radius: 20px; border: 1px solid #ccc; outline: none; color: black;">
                        <button wire:click="sendMessage" 
                            style="background: #25d366; color: white; padding: 0.5rem 1.2rem; border-radius: 20px; font-weight: bold; border: none; cursor: pointer;">
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- Mensaje de "Seleccione un chat" --}}
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const container = document.getElementById('chat-container');
        
        const scrollDown = () => {
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Bajar al cargar y cuando haya cambios
        scrollDown();
        Livewire.on('scroll-down', () => {
            setTimeout(scrollDown, 50);
        });
    });
</script>