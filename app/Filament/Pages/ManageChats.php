<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;

class ManageChats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-oval-left';
    protected static ?string $title = 'Chats';

    protected string $view = 'filament.pages.manage-chats';

}





