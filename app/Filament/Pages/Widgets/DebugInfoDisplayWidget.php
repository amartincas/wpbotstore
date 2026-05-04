<?php

namespace App\Filament\Pages\Widgets;

use Filament\Widgets\Widget;

class DebugInfoDisplayWidget extends Widget
{
    public function getStore()
    {
        return $this->livewire->debugStore ?? 'Not set';
    }

    public function getMessages()
    {
        return $this->livewire->debugMessage ?? 'Not set';
    }
}
