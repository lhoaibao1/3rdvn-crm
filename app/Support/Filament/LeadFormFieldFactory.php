<?php

namespace App\Support\Filament;

use App\Models\SalesProject;
use App\Support\VietnamAddressCatalog;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class LeadFormFieldFactory
{
    public static function componentsForProject(int|string|null $projectId, string $schemaType = 'lead', string $stateRoot = 'payload.fields', bool $disabled = false): array
    {
        $fields = self::fieldsForProject($projectId, $schemaType);

        if ($fields === []) {
            return $schemaType === 'lead' ? self::fallbackComponents() : [];
        }

        $components = collect($fields)
            ->map(fn (array $field): ?object => self::component($field, $stateRoot, $disabled))
            ->filter()
            ->values()
            ->all();

        if (collect($fields)->contains(fn (array $field): bool => self::normalizeKey($field['field_key'] ?? $field['key'] ?? null) === 'province_code')) {
            $components[] = Hidden::make($stateRoot.'.province_name')->dehydrated();
            $components[] = Hidden::make($stateRoot.'.district_name')->dehydrated();
            $components[] = Hidden::make($stateRoot.'.ward_name')->dehydrated();
        }

        return $components;
    }

    public static function entriesForProject(int|string|null $projectId, string $schemaType, string $stateRoot): array
    {
        $fields = self::fieldsForProject($projectId, $schemaType);

        if ($fields === []) {
            return [];
        }

        return collect($fields)
            ->map(function (array $field) use ($stateRoot): ?TextEntry {
                $key = self::normalizeKey($field['field_key'] ?? $field['key'] ?? null);

                if (blank($key)) {
                    return null;
                }

                $label = filled($field['label'] ?? null) ? (string) $field['label'] : str($key)->replace('_', ' ')->title()->toString();
                $type = (string) ($field['type'] ?? 'text');
                $entryPath = match ($key) {
                    'province_code' => $stateRoot.'.province_name',
                    'district_code' => $stateRoot.'.district_name',
                    'ward_code' => $stateRoot.'.ward_name',
                    default => $stateRoot.'.'.$key,
                };
                $entry = TextEntry::make($entryPath)
                    ->label($label)
                    ->placeholder('-');


                if ($type === 'number') {
                    $entry->numeric();
                }

                return $entry;
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function fieldsForProject(int|string|null $projectId, string $schemaType): array
    {
        $project = filled($projectId)
            ? SalesProject::query()->find((int) $projectId)
            : null;

        $fields = match ($schemaType) {
            'module' => $project?->module_form_schema,
            default => $project?->lead_form_schema,
        };

        return is_array($fields) ? $fields : [];
    }

    private static function component(array $field, string $stateRoot, bool $disabled): ?object
    {
        $key = self::normalizeKey($field['field_key'] ?? $field['key'] ?? null);

        if (blank($key)) {
            return null;
        }

        $label = filled($field['label'] ?? null) ? (string) $field['label'] : str($key)->replace('_', ' ')->title()->toString();
        $type = (string) ($field['type'] ?? 'text');
        $statePath = $stateRoot.'.'.$key;
        $required = (bool) ($field['required'] ?? false);
        $placeholder = filled($field['placeholder'] ?? null) ? (string) $field['placeholder'] : null;

        $options = self::options($field["options"] ?? null);

        if ($key === 'province_code') {
            $component = Select::make($statePath)
                ->label($label)
                ->options(fn (): array => VietnamAddressCatalog::provinceOptions())
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) use ($stateRoot): void {
                    $set($stateRoot.'.province_name', VietnamAddressCatalog::provinceName($state));
                    $set($stateRoot.'.district_code', null);
                    $set($stateRoot.'.district_name', null);
                    $set($stateRoot.'.ward_code', null);
                    $set($stateRoot.'.ward_name', null);
                })
                ->native(false);

            if ($required && ! $disabled) {
                $component->required();
            }

            return $disabled ? $component->disabled()->dehydrated(false) : $component;
        }

        if ($key === 'district_code') {
            $component = Select::make($statePath)
                ->label($label)
                ->options(fn (Get $get): array => VietnamAddressCatalog::districtOptions(self::readState($get, $stateRoot, 'province_code')))
                ->disabled(fn (Get $get): bool => $disabled || blank(self::readState($get, $stateRoot, 'province_code')))
                ->placeholder('Chọn quận/huyện')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($stateRoot): void {
                    $provinceCode = self::readState($get, $stateRoot, 'province_code');

                    $set($stateRoot.'.district_name', VietnamAddressCatalog::districtName($provinceCode, $state));
                    $set($stateRoot.'.ward_code', null);
                    $set($stateRoot.'.ward_name', null);
                })
                ->native(false);

            if ($required && ! $disabled) {
                $component->required();
            }

            return $disabled ? $component->dehydrated(false) : $component;
        }

        if ($key === 'ward_code') {
            $component = Select::make($statePath)
                ->label($label)
                ->options(fn (Get $get): array => VietnamAddressCatalog::wardOptions(self::readState($get, $stateRoot, 'district_code')))
                ->disabled(fn (Get $get): bool => $disabled || blank(self::readState($get, $stateRoot, 'district_code')))
                ->placeholder('Chọn phường/xã')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($stateRoot): void {
                    $districtCode = self::readState($get, $stateRoot, 'district_code');

                    $set($stateRoot.'.ward_name', VietnamAddressCatalog::wardName($districtCode, $state));
                })
                ->native(false);

            if ($required && ! $disabled) {
                $component->required();
            }

            return $disabled ? $component->dehydrated(false) : $component;
        }

        $component = match ($type) {
            "textarea" => Textarea::make($statePath)->rows(2),
            "number" => TextInput::make($statePath)->numeric(),
            "date" => TextInput::make($statePath)
                ->mask("99/99/9999")
                ->placeholder("dd/mm/yyyy")
                ->maxLength(10)
                ->rule("date_format:d/m/Y"),
            "select" => TextInput::make($statePath)
                ->datalist(array_values($options)),
            "email" => TextInput::make($statePath)->email(),
            "phone" => TextInput::make($statePath)->tel(),
            default => TextInput::make($statePath),
        };

        $component->label($label);

        if ($required && ! $disabled) {
            $component->required();
        }

        if ($disabled) {
            $component->disabled()->dehydrated(false);
        }

        if (method_exists($component, 'placeholder') && $placeholder) {
            $component->placeholder($placeholder);
        }

        return $component;
    }


    private static function readState(Get $get, string $stateRoot, string $key): mixed
    {
        foreach ([$stateRoot.'.'.$key, $key, '../'.$key] as $path) {
            try {
                $value = $get($path);
            } catch (\Throwable) {
                $value = null;
            }

            if (filled($value)) {
                return $value;
            }
        }

        try {
            $root = $get(str($stateRoot)->before('.')->toString());
        } catch (\Throwable) {
            $root = null;
        }

        $nestedPath = str($stateRoot)->after('.')->append('.'.$key)->toString();
        $value = is_array($root) ? data_get($root, $nestedPath) : null;

        return filled($value) ? $value : null;
    }

    private static function fallbackComponents(): array
    {
        return [
            TextInput::make('payload.fields.lead_name')
                ->label('Tên lead')
                ->required()
                ->maxLength(255),
            TextInput::make('payload.fields.phone')
                ->label('Số điện thoại')
                ->tel()
                ->maxLength(30),
            TextInput::make('payload.fields.email')
                ->label('Email')
                ->email()
                ->maxLength(255),
        ];
    }

    private static function normalizeKey(mixed $key): ?string
    {
        if (blank($key)) {
            return null;
        }

        return Str::of((string) $key)
            ->ascii()
            ->lower()
            ->replace([' ', '-', '.'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->trim('_')
            ->toString();
    }

    private static function options(mixed $rawOptions): array
    {
        if (blank($rawOptions)) {
            return [];
        }

        if (is_string($rawOptions)) {
            $rawOptions = preg_split('/\r\n|\r|\n/', $rawOptions) ?: [];
        }

        if (! is_array($rawOptions)) {
            return [];
        }

        return collect($rawOptions)
            ->mapWithKeys(function (mixed $value, mixed $key): array {
                if (is_array($value)) {
                    $optionValue = trim((string) ($value['value'] ?? $value['label'] ?? reset($value)));
                    $optionLabel = trim((string) ($value['label'] ?? $value['value'] ?? $optionValue));
                } elseif (is_string($key)) {
                    $optionValue = trim($key);
                    $optionLabel = trim((string) $value);
                } else {
                    $optionValue = trim((string) $value);
                    $optionLabel = $optionValue;
                }

                return $optionValue !== '' ? [$optionValue => ($optionLabel !== '' ? $optionLabel : $optionValue)] : [];
            })
            ->all();
    }
}
