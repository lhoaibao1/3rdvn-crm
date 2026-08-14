<?php

namespace App\Enums;

enum FeDeeplinkStatus: string
{
    case REJECT = 'reject';
    case END = 'end';

    public function label(): string
    {
        return match ($this) {
            self::REJECT => 'Reject',
            self::END => 'END',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REJECT => 'danger',
            self::END => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [
            $status->value => $status->label(),
        ])->all();
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? ($value ?: '-');
    }

    public static function colorFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->color() ?? 'gray';
    }
}
