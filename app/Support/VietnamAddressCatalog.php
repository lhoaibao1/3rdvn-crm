<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class VietnamAddressCatalog
{
    private static ?array $data = null;

    /** @return array<string, string> */
    public static function provinceOptions(): array
    {
        $tree = self::loadData();
        $options = [];
        foreach ($tree as $prov) {
            $code = (string) ($prov['Id'] ?? $prov['code'] ?? '');
            $name = (string) ($prov['Name'] ?? $prov['name'] ?? '');
            if ($code !== '' && $name !== '') {
                $options[$code] = $name;
            }
        }
        return $options;
    }

    /** @return array<string, string> */
    public static function districtOptions(null|int|string $provinceCode): array
    {
        if (! $provinceCode) {
            return [];
        }

        $prov = self::findProvince($provinceCode);
        if (! $prov) {
            return [];
        }

        $options = [];
        foreach ($prov['Districts'] ?? $prov['districts'] ?? [] as $dist) {
            $code = (string) ($dist['Id'] ?? $dist['code'] ?? '');
            $name = (string) ($dist['Name'] ?? $dist['name'] ?? '');
            if ($code !== '' && $name !== '') {
                $options[$code] = $name;
            }
        }
        return $options;
    }

    /** @return array<string, string> */
    public static function wardOptions(null|int|string $districtCode): array
    {
        if (! $districtCode) {
            return [];
        }

        $dist = self::findDistrict($districtCode);
        if (! $dist) {
            return [];
        }

        $options = [];
        foreach ($dist['Wards'] ?? $dist['wards'] ?? [] as $ward) {
            $code = (string) ($ward['Id'] ?? $ward['code'] ?? '');
            $name = (string) ($ward['Name'] ?? $ward['name'] ?? '');
            if ($code !== '' && $name !== '') {
                $options[$code] = $name;
            }
        }
        return $options;
    }

    public static function provinceName(null|int|string $code): ?string
    {
        if (! $code) return null;
        $prov = self::findProvince($code);
        return $prov ? ($prov['Name'] ?? $prov['name'] ?? null) : null;
    }

    public static function districtName(null|int|string $provinceCode, null|int|string $districtCode): ?string
    {
        if (! $districtCode) return null;
        $dist = self::findDistrict($districtCode, $provinceCode);
        return $dist ? ($dist['Name'] ?? $dist['name'] ?? null) : null;
    }

    public static function wardName(null|int|string $districtCode, null|int|string $wardCode): ?string
    {
        if (! $wardCode) return null;
        $ward = self::findWard($wardCode, $districtCode);
        return $ward ? ($ward['Name'] ?? $ward['name'] ?? null) : null;
    }

    /** @return array<string, int|string|null>|null */
    public static function districtInfo(null|int|string $districtCode): ?array
    {
        if (! $districtCode) return null;
        $tree = self::loadData();
        foreach ($tree as $prov) {
            foreach ($prov['Districts'] ?? $prov['districts'] ?? [] as $dist) {
                if ((string) ($dist['Id'] ?? $dist['code'] ?? '') === (string) $districtCode) {
                    return [
                        'province_code' => $prov['Id'] ?? $prov['code'] ?? null,
                        'province_name' => $prov['Name'] ?? $prov['name'] ?? null,
                        'district_code' => $dist['Id'] ?? $dist['code'] ?? null,
                        'district_name' => $dist['Name'] ?? $dist['name'] ?? null,
                    ];
                }
            }
        }
        return null;
    }

    /** @return array<string, int|string|null>|null */
    public static function wardInfo(null|int|string $wardCode): ?array
    {
        if (! $wardCode) return null;
        $tree = self::loadData();
        foreach ($tree as $prov) {
            foreach ($prov['Districts'] ?? $prov['districts'] ?? [] as $dist) {
                foreach ($dist['Wards'] ?? $dist['wards'] ?? [] as $ward) {
                    if ((string) ($ward['Id'] ?? $ward['code'] ?? '') === (string) $wardCode) {
                        return [
                            'province_code' => $prov['Id'] ?? $prov['code'] ?? null,
                            'province_name' => $prov['Name'] ?? $prov['name'] ?? null,
                            'district_code' => $dist['Id'] ?? $dist['code'] ?? null,
                            'district_name' => $dist['Name'] ?? $dist['name'] ?? null,
                            'ward_code' => $ward['Id'] ?? $ward['code'] ?? null,
                            'ward_name' => $ward['Name'] ?? $ward['name'] ?? null,
                        ];
                    }
                }
            }
        }
        return null;
    }

    private static function findProvince(null|int|string $code): ?array
    {
        if (! $code) return null;
        $codeStr = (string) $code;
        foreach (self::loadData() as $prov) {
            if ((string) ($prov['Id'] ?? $prov['code'] ?? '') === $codeStr || strcasecmp($prov['Name'] ?? $prov['name'] ?? '', $codeStr) === 0) {
                return $prov;
            }
        }
        return null;
    }

    private static function findDistrict(null|int|string $districtCode, null|int|string $provinceCode = null): ?array
    {
        if (! $districtCode) return null;
        $distCodeStr = (string) $districtCode;

        if ($provinceCode) {
            $prov = self::findProvince($provinceCode);
            if ($prov) {
                foreach ($prov['Districts'] ?? $prov['districts'] ?? [] as $dist) {
                    if ((string) ($dist['Id'] ?? $dist['code'] ?? '') === $distCodeStr || strcasecmp($dist['Name'] ?? $dist['name'] ?? '', $distCodeStr) === 0) {
                        return $dist;
                    }
                }
            }
        }

        foreach (self::loadData() as $prov) {
            foreach ($prov['Districts'] ?? $prov['districts'] ?? [] as $dist) {
                if ((string) ($dist['Id'] ?? $dist['code'] ?? '') === $distCodeStr || strcasecmp($dist['Name'] ?? $dist['name'] ?? '', $distCodeStr) === 0) {
                    return $dist;
                }
            }
        }
        return null;
    }

    private static function findWard(null|int|string $wardCode, null|int|string $districtCode = null): ?array
    {
        if (! $wardCode) return null;
        $wardCodeStr = (string) $wardCode;

        if ($districtCode) {
            $dist = self::findDistrict($districtCode);
            if ($dist) {
                foreach ($dist['Wards'] ?? $dist['wards'] ?? [] as $ward) {
                    if ((string) ($ward['Id'] ?? $ward['code'] ?? '') === $wardCodeStr || strcasecmp($ward['Name'] ?? $ward['name'] ?? '', $wardCodeStr) === 0) {
                        return $ward;
                    }
                }
            }
        }

        foreach (self::loadData() as $prov) {
            foreach ($prov['Districts'] ?? $prov['districts'] ?? [] as $dist) {
                foreach ($dist['Wards'] ?? $dist['wards'] ?? [] as $ward) {
                    if ((string) ($ward['Id'] ?? $ward['code'] ?? '') === $wardCodeStr || strcasecmp($ward['Name'] ?? $ward['name'] ?? '', $wardCodeStr) === 0) {
                        return $ward;
                    }
                }
            }
        }
        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function loadData(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $paths = [
            storage_path('app/vietnam_divisions.json'),
            base_path('storage/app/vietnam_divisions.json'),
            '/opt/3rdvn-sso/data/vietnam_divisions.json',
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $raw = file_get_contents($p);
                $json = json_decode($raw, true);
                if (is_array($json) && count($json) > 0) {
                    self::$data = $json;
                    return self::$data;
                }
            }
        }

        self::$data = [];
        return self::$data;
    }
}
