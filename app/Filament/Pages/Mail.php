<?php

namespace App\Filament\Pages;

use App\Models\UiSetting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Mail extends Page
{
    protected static ?string $slug = 'mail';

    protected static ?string $title = 'Mail';

    protected static ?string $navigationLabel = 'Mail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Service';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.mail';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    protected function getViewData(): array
    {
        $settings = UiSetting::current();
        $user = auth()->user();
        $fields = $settings->mail_user_meta_fields
            ?: ['uid', 'employee_code', 'role', 'department', 'company', 'branch'];

        $availableMeta = [
            'uid' => ['label' => 'UID', 'value' => $user?->uid],
            'employee_code' => ['label' => 'Mã NV', 'value' => $user?->employee_code],
            'role' => ['label' => 'Vai trò', 'value' => $user?->getRoleNames()->first()],
            'department' => ['label' => 'Phòng ban', 'value' => $user?->department],
            'position' => ['label' => 'Chức danh', 'value' => $user?->position],
            'company' => ['label' => 'Công ty', 'value' => $user?->company_name],
            'branch' => ['label' => 'Chi nhánh', 'value' => $user?->branch_name],
            'office' => ['label' => 'Văn phòng', 'value' => $user?->office],
        ];

        $meta = collect($fields)
            ->map(fn (string $field): ?array => $availableMeta[$field] ?? null)
            ->filter(fn (?array $item): bool => filled($item['value'] ?? null))
            ->values()
            ->all();

        return [
            'mailSettings' => $settings,
            'mailUser' => $user,
            'mailMeta' => $meta,
            'mailAvatarUrl' => $user?->wirechat_avatar_url,
        ];
    }

    public function mailboxUrl(): string
    {
        return route('mail.sso');
    }
}
