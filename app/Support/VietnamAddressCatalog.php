<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VietnamAddressCatalog
{
    /** @return array<int|string, string> */
    public static function provinceOptions(): array
    {
        return collect(self::provinces())->mapWithKeys(fn (array $item): array => [(string) $item['code'] => $item['name']])->all();
    }

    /** @return array<int|string, string> */
    public static function districtOptions(null|int|string $provinceCode): array
    {
        if (! $provinceCode) {
            return [];
        }

        return collect(self::districts($provinceCode))->mapWithKeys(fn (array $item): array => [(string) $item['code'] => $item['name']])->all();
    }

    /** @return array<int|string, string> */
    public static function wardOptions(null|int|string $districtCode): array
    {
        if (! $districtCode) {
            return [];
        }

        return collect(self::wards($districtCode))->mapWithKeys(fn (array $item): array => [(string) $item['code'] => $item['name']])->all();
    }



    /** @return array<int|string, string> */
    public static function allDistrictSimpleOptions(): array
    {
        return collect(self::locationTree())
            ->flatMap(fn (array $province): array => collect($province['districts'] ?? [])
                ->mapWithKeys(fn (array $district): array => [
                    (string) $district['code'] => (string) $district['name'],
                ])
                ->all())
            ->all();
    }

    /** @return array<int|string, string> */
    public static function allWardSimpleOptions(): array
    {
        return collect(self::locationTree())
            ->flatMap(fn (array $province): array => collect($province['districts'] ?? [])
                ->flatMap(fn (array $district): array => collect($district['wards'] ?? [])
                    ->mapWithKeys(fn (array $ward): array => [
                        (string) $ward['code'] => (string) $ward['name'],
                    ])
                    ->all())
                ->all())
            ->all();
    }

    /** @return array<int|string, string> */
    public static function allDistrictOptions(): array
    {
        return collect(self::locationTree())
            ->flatMap(fn (array $province): array => collect($province['districts'] ?? [])
                ->mapWithKeys(fn (array $district): array => [
                    (string) $district['code'] => $province['name'].' / '.$district['name'],
                ])
                ->all())
            ->all();
    }

    /** @return array<int|string, string> */
    public static function allWardOptions(): array
    {
        return collect(self::locationTree())
            ->flatMap(fn (array $province): array => collect($province['districts'] ?? [])
                ->flatMap(fn (array $district): array => collect($district['wards'] ?? [])
                    ->mapWithKeys(fn (array $ward): array => [
                        (string) $ward['code'] => $province['name'].' / '.$district['name'].' / '.$ward['name'],
                    ])
                    ->all())
                ->all())
            ->all();
    }

    /** @return array<string, int|string|null>|null */
    public static function districtInfo(null|int|string $districtCode): ?array
    {
        if (! $districtCode) {
            return null;
        }

        foreach (self::locationTree() as $province) {
            foreach ($province['districts'] ?? [] as $district) {
                if ((string) ($district['code'] ?? '') === (string) $districtCode) {
                    return [
                        'province_code' => $province['code'] ?? null,
                        'province_name' => $province['name'] ?? null,
                        'district_code' => $district['code'] ?? null,
                        'district_name' => $district['name'] ?? null,
                    ];
                }
            }
        }

        return null;
    }

    /** @return array<string, int|string|null>|null */
    public static function wardInfo(null|int|string $wardCode): ?array
    {
        if (! $wardCode) {
            return null;
        }

        foreach (self::locationTree() as $province) {
            foreach ($province['districts'] ?? [] as $district) {
                foreach ($district['wards'] ?? [] as $ward) {
                    if ((string) ($ward['code'] ?? '') === (string) $wardCode) {
                        return [
                            'province_code' => $province['code'] ?? null,
                            'province_name' => $province['name'] ?? null,
                            'district_code' => $district['code'] ?? null,
                            'district_name' => $district['name'] ?? null,
                            'ward_code' => $ward['code'] ?? null,
                            'ward_name' => $ward['name'] ?? null,
                        ];
                    }
                }
            }
        }

        return null;
    }

    public static function provinceName(null|int|string $code): ?string
    {
        return self::findName(self::provinces(), $code);
    }

    public static function districtName(null|int|string $provinceCode, null|int|string $districtCode): ?string
    {
        return self::findName(self::districts($provinceCode), $districtCode);
    }

    public static function wardName(null|int|string $districtCode, null|int|string $wardCode): ?string
    {
        return self::findName(self::wards($districtCode), $wardCode);
    }


    /** @return array<int, array<mixed>> */
    private static function locationTree(): array
    {
        return Cache::remember('vn_address.location_tree.depth_3', now()->addDays(7), function (): array {
            return self::normalizeTree(self::fetchPayload('https://provinces.open-api.vn/api/?depth=3'));
        });
    }

    /** @param array<mixed> $items @return array<int, array<mixed>> */
    private static function normalizeTree(array $items): array
    {
        return collect($items)
            ->map(function (array $province): array {
                return [
                    'code' => $province['code'] ?? '',
                    'name' => (string) ($province['name'] ?? ''),
                    'districts' => collect($province['districts'] ?? [])
                        ->map(function (array $district): array {
                            return [
                                'code' => $district['code'] ?? '',
                                'name' => (string) ($district['name'] ?? ''),
                                'wards' => collect($district['wards'] ?? [])
                                    ->map(fn (array $ward): array => [
                                        'code' => $ward['code'] ?? '',
                                        'name' => (string) ($ward['name'] ?? ''),
                                    ])
                                    ->filter(fn (array $ward): bool => $ward['code'] !== '' && $ward['name'] !== '')
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->filter(fn (array $district): bool => $district['code'] !== '' && $district['name'] !== '')
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $province): bool => $province['code'] !== '' && $province['name'] !== '')
            ->values()
            ->all();
    }

    /** @return array<int, array{code: int|string, name: string}> */
    private static function provinces(): array
    {
        return Cache::remember('vn_address.provinces', now()->addDays(7), function (): array {
            return self::fetchList('https://provinces.open-api.vn/api/?depth=1');
        });
    }

    /** @return array<int, array{code: int|string, name: string}> */
    private static function districts(null|int|string $provinceCode): array
    {
        if (! $provinceCode) {
            return [];
        }

        return Cache::remember("vn_address.province.{$provinceCode}.districts", now()->addDays(7), function () use ($provinceCode): array {
            $payload = self::fetchPayload("https://provinces.open-api.vn/api/p/{$provinceCode}?depth=2");

            return self::normalizeList($payload['districts'] ?? []);
        });
    }

    /** @return array<int, array{code: int|string, name: string}> */
    private static function wards(null|int|string $districtCode): array
    {
        if (! $districtCode) {
            return [];
        }

        return Cache::remember("vn_address.district.{$districtCode}.wards", now()->addDays(7), function () use ($districtCode): array {
            $payload = self::fetchPayload("https://provinces.open-api.vn/api/d/{$districtCode}?depth=2");

            return self::normalizeList($payload['wards'] ?? []);
        });
    }

    /** @return array<int, array{code: int|string, name: string}> */
    private static function fetchList(string $url): array
    {
        return self::normalizeList(self::fetchPayload($url));
    }

    /** @return array<mixed> */
    private static function fetchPayload(string $url): array
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get($url);

            if ($response->successful()) {
                return $response->json() ?: [];
            }
        } catch (\Throwable) {
            // Address selects can stay empty if the public API is temporarily unavailable.
        }

        return [];
    }

    /** @param array<mixed> $items @return array<int, array{code: int|string, name: string}> */
    private static function normalizeList(array $items): array
    {
        return collect($items)
            ->map(fn (array $item): array => ['code' => $item['code'] ?? '', 'name' => (string) ($item['name'] ?? '')])
            ->filter(fn (array $item): bool => $item['code'] !== '' && $item['name'] !== '')
            ->values()
            ->all();
    }

    /** @param array<int, array{code: int|string, name: string}> $items */
    private static function findName(array $items, null|int|string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $item = collect($items)->first(fn (array $item): bool => (string) $item['code'] === (string) $code);

        return $item['name'] ?? null;
    }
}
