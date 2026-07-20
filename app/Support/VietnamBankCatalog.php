<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VietnamBankCatalog
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::banks())
            ->mapWithKeys(fn (array $bank): array => [
                $bank['code'] => trim(($bank['short_name'] ?? $bank['code']).' - '.($bank['name'] ?? $bank['code'])),
            ])
            ->sort()
            ->all();
    }

    public static function nameFor(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $bank = collect(self::banks())->firstWhere('code', $code);

        return $bank['name'] ?? null;
    }

    /**
     * @return array<int, array{code: string, short_name: string, name: string}>
     */
    public static function banks(): array
    {
        return Cache::remember('vietnam_bank_catalog.vietqr', now()->addDay(), function (): array {
            try {
                $response = Http::timeout(6)->acceptJson()->get('https://api.vietqr.io/v2/banks');

                if ($response->successful()) {
                    $banks = collect($response->json('data', []))
                        ->map(fn (array $bank): array => [
                            'code' => (string) ($bank['code'] ?? $bank['bin'] ?? ''),
                            'short_name' => (string) ($bank['shortName'] ?? $bank['short_name'] ?? $bank['code'] ?? ''),
                            'name' => (string) ($bank['name'] ?? ''),
                        ])
                        ->filter(fn (array $bank): bool => $bank['code'] !== '' && $bank['name'] !== '')
                        ->values()
                        ->all();

                    if ($banks !== []) {
                        return $banks;
                    }
                }
            } catch (\Throwable) {
                // Keep the CRM usable if the public bank API is temporarily down.
            }

            return self::fallbackBanks();
        });
    }

    /**
     * @return array<int, array{code: string, short_name: string, name: string}>
     */
    private static function fallbackBanks(): array
    {
        return [
            ['code' => 'VCB', 'short_name' => 'Vietcombank', 'name' => 'Ngân hàng TMCP Ngoại thương Việt Nam'],
            ['code' => 'TCB', 'short_name' => 'Techcombank', 'name' => 'Ngân hàng TMCP Kỹ thương Việt Nam'],
            ['code' => 'ACB', 'short_name' => 'ACB', 'name' => 'Ngân hàng TMCP Á Châu'],
            ['code' => 'BIDV', 'short_name' => 'BIDV', 'name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam'],
            ['code' => 'CTG', 'short_name' => 'VietinBank', 'name' => 'Ngân hàng TMCP Công thương Việt Nam'],
            ['code' => 'MB', 'short_name' => 'MBBank', 'name' => 'Ngân hàng TMCP Quân đội'],
            ['code' => 'VPB', 'short_name' => 'VPBank', 'name' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng'],
            ['code' => 'TPB', 'short_name' => 'TPBank', 'name' => 'Ngân hàng TMCP Tiên Phong'],
            ['code' => 'STB', 'short_name' => 'Sacombank', 'name' => 'Ngân hàng TMCP Sài Gòn Thương Tín'],
            ['code' => 'HDB', 'short_name' => 'HDBank', 'name' => 'Ngân hàng TMCP Phát triển TP.HCM'],
        ];
    }
}
