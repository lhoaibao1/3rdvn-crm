<?php

namespace App\Providers\Wirechat;

use App\Models\UiSetting;
use App\Models\User;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;
use Wirechat\Wirechat\Support\Color;

class ChatsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('chats')
            ->path('_wirechat')
            ->layout('layouts.wirechat')
            ->heading('Trò chuyện')
            ->favicon(fn () => ($path = UiSetting::current()->favicon_path) ? asset('storage/'.$path) : null)
            ->homeUrl(url('/'))
            ->chatsSearch()
            ->searchableAttributes(['name', 'uid', 'employee_code', 'email', 'phone'])
            ->searchUsersUsing(fn (?string $needle) => User::query()
                ->whereKeyNot(auth()->id())
                ->whereNotIn('employment_status', ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED])
                ->where(function ($query) use ($needle): void {
                    foreach (['name', 'uid', 'employee_code', 'email', 'phone'] as $field) {
                        $query->orWhere($field, 'ilike', '%'.trim((string) $needle).'%');
                    }
                })
                ->orderBy('name')
                ->limit(30)
                ->get())
            ->redirectToHomeAction()
            ->createChatAction()
            ->groups()
            ->createGroupAction()
            ->maxGroupMembers(100)
            ->attachments()
            ->maxUploads(5)
            ->mediaMimes(['png', 'jpg', 'jpeg', 'webp', 'gif', 'mov', 'mp4'])
            ->mediaMaxUploadSize(10240)
            ->fileMimes(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'])
            ->fileMaxUploadSize(10240)
            ->emojiPicker()
            ->parseMessageUrls()
            ->broadcasting()
            ->webPushNotifications()
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
                'dark' => Color::Slate,
            ])
            ->middleware(['web', 'auth'])
            ->default();
    }
}
