<?php

namespace App\Support\Applications;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LeadPayload
{
    public const FIELD_PATH = 'fields';

    public static function fields(array $payload): array
    {
        $fields = data_get($payload, self::FIELD_PATH, []);

        return is_array($fields) ? $fields : [];
    }

    public static function primaryName(array $payload, ?string $fallback = null): ?string
    {
        return self::firstFilled($payload, ['lead_name', 'customer_name', 'full_name', 'name', 'ho_ten', 'hoten']) ?: $fallback;
    }

    public static function phone(array $payload, ?string $fallback = null): ?string
    {
        return self::firstFilled($payload, ['phone', 'sdt', 'mobile', 'phone_number']) ?: $fallback;
    }

    public static function email(array $payload, ?string $fallback = null): ?string
    {
        return self::firstFilled($payload, ['email', 'mail']) ?: $fallback;
    }

    public static function identityNumber(array $payload, ?string $fallback = null): ?string
    {
        return self::firstFilled($payload, ['identity_number', 'cccd', 'cmnd', 'id_number']) ?: $fallback;
    }

    public static function firstFilledValue(array $payload): ?string
    {
        foreach (Arr::flatten(self::fields($payload)) as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private static function firstFilled(array $payload, array $keys): ?string
    {
        $fields = self::fields($payload);
        $normalized = [];

        foreach ($fields as $key => $value) {
            $normalized[Str::of((string) $key)->lower()->replace([' ', '-', '.'], '_')->toString()] = $value;
        }

        foreach ($keys as $key) {
            $value = $normalized[$key] ?? null;

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
