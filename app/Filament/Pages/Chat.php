<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\PanelRegistry;

class Chat extends Page
{
    protected static ?string $slug = 'tro-chuyen';

    protected static ?string $title = 'Trò chuyện';

    protected static ?string $navigationLabel = 'Trò chuyện';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string | \UnitEnum | null $navigationGroup = 'Service';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.chat';

    protected Width | string | null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        app(PanelRegistry::class)->setCurrent('chats');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->canAccessWirechatPanel(Wirechat::getPanel('chats')));
    }

    public static function getNavigationBadge(): ?string
    {
        $count = auth()->user()?->getUnreadCount() ?? 0;

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'primary';
    }

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }
}
