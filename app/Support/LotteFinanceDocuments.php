<?php

namespace App\Support;

use App\Models\Application;
use App\Support\Applications\LotteFinanceWorkflow;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class LotteFinanceDocuments
{
    private const CONVERT_TO_PDF = [
        'doc100',
        'doc105_customer_sale',
        'doc133_salary',
        'doc141_customer',
        'doc105_lookup',
        'doc157_vssid',
        'doc158_insurance',
        'doc159_kyc',
    ];

    public static function definitions(): array
    {
        return [
            'doc100' => 'DOC100 - CMND/CCCD/CMTQĐ/Hộ chiếu',
            'doc101' => 'DOC101 - Giấy tờ chứng minh cư trú',
            'doc105_customer_sale' => 'DOC105 - Ảnh chụp KH với Sale',
            'doc133_salary' => 'DOC133 - Tài liệu Lương',
            'doc141_customer' => 'DOC141 - Ảnh chụp KH',
            'doc105_lookup' => 'DOC105 - Màn hình tra cứu',
            'doc157_vssid' => 'DOC157 - VSSID',
            'doc1571_vssid_video' => 'DOC1571 - VSSID Đối chiếu (Video)',
            'doc158_insurance' => 'DOC158 - Giấy YCBH khoản vay',
            'doc159_kyc' => 'DOC159 - Tài liệu KYC',
        ];
    }

    public static function components(bool|\Closure $disabled = false): array
    {
        return [
            Section::make('Chứng từ Lotte Finance')
                ->description('Ảnh được tự động gom và nén thành PDF. DOC101, DOC1571 và file PDF tải lên được giữ nguyên.')
                ->disabled($disabled)
                ->columns(2)
                ->schema([
                    self::upload('doc100', self::definitions()['doc100'])
                        ->disabled()
                        ->dehydrated(false),
                    self::upload('doc101', self::definitions()['doc101']),
                    self::upload('doc105_customer_sale', self::definitions()['doc105_customer_sale']),
                    self::upload('doc133_salary', self::definitions()['doc133_salary']),
                    self::upload('doc141_customer', self::definitions()['doc141_customer']),
                    self::upload('doc105_lookup', self::definitions()['doc105_lookup']),
                    self::upload('doc157_vssid', self::definitions()['doc157_vssid']),
                    FileUpload::make('payload.documents.doc1571_vssid_video')
                        ->label(self::definitions()['doc1571_vssid_video'])
                        ->disk('public')
                        ->directory('applications/lotte-finance/doc1571')
                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                        ->maxSize(102400)
                        ->multiple()
                        ->maxFiles(3)
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                    self::upload('doc158_insurance', self::definitions()['doc158_insurance']),
                    self::upload('doc159_kyc', self::definitions()['doc159_kyc']),
                ]),
        ];
    }

    public static function normalizeUploads(Application $application): void
    {
        if (! LotteFinanceWorkflow::isLotteFinance($application)) {
            return;
        }

        $payload = is_array($application->payload) ? $application->payload : [];
        $documents = is_array($payload['documents'] ?? null) ? $payload['documents'] : [];
        $doc100Sources = array_values(array_filter([
            data_get($payload, 'fields.ocr_front_image'),
            data_get($payload, 'fields.ocr_back_image'),
        ], fn (mixed $path): bool => filled($path)));

        if ($doc100Sources !== [] && empty($documents['doc100'])) {
            $documents['doc100'] = $doc100Sources;
        }

        foreach (self::CONVERT_TO_PDF as $key) {
            $documents[$key] = self::convertImages($application, $key, $documents[$key] ?? [], $key === 'doc100');
        }

        $payload['documents'] = array_filter(
            $documents,
            fn (mixed $value): bool => self::paths($value) !== [],
        );

        $application->updateQuietly(['payload' => $payload]);
    }

    private static function upload(string $key, string $label): FileUpload
    {
        return FileUpload::make('payload.documents.'.$key)
            ->label($label)
            ->disk('public')
            ->directory('applications/lotte-finance/'.$key)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
            ->maxSize(15360)
            ->multiple()
            ->maxFiles(12)
            ->reorderable()
            ->downloadable()
            ->openable();
    }

    private static function convertImages(Application $application, string $key, mixed $value, bool $keepSources): array
    {
        $paths = self::paths($value);
        $pdfs = array_values(array_filter($paths, fn (string $path): bool => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf'));
        $images = array_values(array_filter($paths, fn (string $path): bool => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)));

        if ($images === []) {
            return $paths;
        }

        $disk = Storage::disk('public');
        $sourcePaths = array_values(array_filter(
            array_map(fn (string $path): ?string => $disk->exists($path) ? $disk->path($path) : null, $images),
        ));

        if ($sourcePaths === [] || ! self::converterAvailable()) {
            Log::warning('Không thể chuyển chứng từ Lotte sang PDF.', [
                'application_id' => $application->getKey(),
                'document' => $key,
                'reason' => $sourcePaths === [] ? 'Không tìm thấy file nguồn.' : 'Thiếu lệnh img2pdf.',
            ]);

            return $paths;
        }

        $directory = 'applications/lotte-finance/'.$application->getKey();
        $disk->makeDirectory($directory);
        $output = $directory.'/'.$key.'-'.Str::uuid().'.pdf';
        $command = ['img2pdf', ...$sourcePaths, '--output', $disk->path($output)];
        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! $disk->exists($output)) {
            Log::warning('Chuyển chứng từ Lotte sang PDF thất bại.', [
                'application_id' => $application->getKey(),
                'document' => $key,
                'error' => $process->getErrorOutput(),
            ]);

            return $paths;
        }

        if (! $keepSources) {
            $disk->delete($images);
        }

        return [...$pdfs, $output];
    }

    private static function paths(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn (mixed $path): string => trim((string) $path), $values),
            fn (string $path): bool => $path !== '',
        ));
    }

    private static function converterAvailable(): bool
    {
        static $available;

        return $available ??= is_executable('/usr/bin/img2pdf');
    }
}
