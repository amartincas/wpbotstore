<div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 0; border: 1px solid #e5e7eb; height: 80vh; overflow: hidden; border-radius: 12px; background: white;">
    
    <div wire:poll.10s style="grid-column: span 4 / span 4; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; background: #f9fafb;">
        <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: white;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">Chats</h2>
            
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->is_super_admin): ?>
                <select wire:model.live="filterStoreId" 
                    style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; color: #111827;">
                    <option value="">All Stores</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($store->id); ?>"><?php echo e($store->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <div style="flex: 1; overflow-y: auto;">
            <div style="display: flex; flex-direction: column;">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    
                    <?php
                        $storeName = null;
                        if(Auth::user()->is_super_admin) {
                            $firstMsg = \App\Models\WhatsAppMessage::where('customer_phone', $conversation->customer_phone)->first();
                            if($firstMsg) {
                                $storeName = $firstMsg->store?->name;
                            }
                        }
                    ?>
                    
                    <button wire:click="selectConversation('<?php echo e($conversation->customer_phone); ?>')"
                        style="width: 100%; text-align: left; padding: 1rem; border-bottom: 1px solid #f3f4f6; background: <?php echo e($selectedPhone === $conversation->customer_phone ? '#eff6ff' : 'white'); ?>; border-left: <?php echo e($selectedPhone === $conversation->customer_phone ? '4px solid #2563eb' : 'none'); ?>;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #111827;"><?php echo e($conversation->customer_phone); ?></span>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($storeName): ?>
                            <br><span style="font-size: 0.75rem; color: #6b7280;"><?php echo e($storeName); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    </div>

    <div style="grid-column: span 8 / span 8; display: flex; flex-direction: column; background: #e5ddd5; position: relative; height: 100%; overflow: hidden;">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedPhone): ?>
            <!-- Contenedor de la derecha (Mensajes) -->
            <div style="display: flex; flex-direction: column; height: 100%; min-height: 500px;">
                
                <!-- Cabecera -->
                <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: white; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-weight: 700; color: #111827; margin: 0;"><?php echo e($selectedPhone); ?></h3>
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
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div style="display: flex; width: 100%; justify-content: <?php echo e($message->role === 'user' ? 'flex-start' : 'flex-end'); ?>;">
                            <div style="max-width: 75%; padding: 0.6rem 1rem; border-radius: 12px; background: <?php echo e($message->role === 'user' ? 'white' : '#dcf8c6'); ?>; box-shadow: 0 1px 1px rgba(0,0,0,0.1);">
                                <p style="font-size: 14px; margin: 0; word-wrap: break-word; white-space: pre-wrap; color: #111827;"><?php echo e($message->content); ?></p>
                                <div style="font-size: 10px; color: #6b7280; text-align: right; margin-top: 4px;"><?php echo e($message->created_at->format('H:i')); ?></div>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
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
        <?php else: ?>
            
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
</script><?php /**PATH C:\Users\amart\Herd\WpBotStore\resources\views/livewire/whats-app-chat-center.blade.php ENDPATH**/ ?>