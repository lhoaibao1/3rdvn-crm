<?php

namespace App\Support\DataCenter;

use App\Models\User;
use App\Support\Notifications\DataCenterNotificationSender;
use App\Support\Permissions\DataCenterAccess;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class DataCenterCsvImporter
{
    public static function import(string $path, User $actor): array
    {
        if (! DataCenterAccess::canDistribute($actor)) {
            abort(403);
        }

        $reader = new Reader;

        try {
            $reader->open($path);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'Không thể đọc file Excel. Vui lòng tải và sử dụng đúng file mẫu .xlsx.',
            ]);
        }

        try {
            $headers = null;
            $created = 0;
            $skipped = 0;
            $errors = [];
            $assignments = [];
            $rowNumber = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $excelRow) {
                    $rowNumber++;
                    $values = array_map(
                        static fn ($cell): mixed => $cell->getValue(),
                        $excelRow->getCells(),
                    );

                    if (self::isEmpty($values)) {
                        continue;
                    }

                    if ($headers === null) {
                        $headers = array_map(
                            fn (mixed $header): string => self::key((string) $header),
                            $values,
                        );
                        self::validateHeaders($headers);

                        continue;
                    }

                    $values = array_pad($values, count($headers), null);
                    $row = array_combine($headers, array_slice($values, 0, count($headers))) ?: [];
                    $name = self::text(self::value($row, ['ho_ten_khach_hang', 'ho_ten', 'ho_va_ten', 'customer_name', 'ten_khach_hang']));
                    $phone = self::text(self::value($row, ['so_dien_thoai', 'sdt', 'phone']));
                    $assigneeUid = self::text(self::value($row, ['uid_nguoi_xu_ly', 'assigned_uid']));
                    $assignee = self::findAssignee($actor, $assigneeUid);

                    if ($name === '') {
                        $skipped++;
                        $errors[] = "Dòng {$rowNumber}: thiếu Họ tên khách hàng.";

                        continue;
                    }

                    if ($phone === '') {
                        $skipped++;
                        $errors[] = "Dòng {$rowNumber}: thiếu Số điện thoại.";

                        continue;
                    }

                    if ($assigneeUid === '') {
                        $skipped++;
                        $errors[] = "Dòng {$rowNumber}: thiếu UID người xử lý.";

                        continue;
                    }

                    if (! $assignee instanceof User) {
                        $skipped++;
                        $errors[] = "Dòng {$rowNumber}: UID {$assigneeUid} không tồn tại hoặc ngoài phạm vi phân bổ.";

                        continue;
                    }

                    DataCenterLeadService::create([
                        'customer_name' => $name,
                        'phone' => $phone,
                        'email' => self::nullable(self::value($row, ['email', 'email_khach_hang'])),
                        'identity_number' => self::nullable(self::value($row, ['cccd_cmnd', 'cccd', 'cmnd', 'identity_number'])),
                        'date_of_birth' => self::date(self::value($row, ['ngay_sinh', 'date_of_birth'])),
                        'address' => self::nullable(self::value($row, ['dia_chi_chi_tiet', 'dia_chi', 'address'])),
                        'province_name' => self::nullable(self::value($row, ['tinh_thanh_pho', 'tinh', 'province'])),
                        'district_name' => self::nullable(self::value($row, ['quan_huyen', 'district'])),
                        'ward_name' => self::nullable(self::value($row, ['phuong_xa', 'ward'])),
                        'source' => self::nullable(self::value($row, ['nguon', 'source'])) ?: 'Import Lead Referral',
                        'payload' => [
                            'import' => [
                                'row' => $rowNumber,
                                'assigned_uid' => $assignee->uid,
                            ],
                        ],
                    ], $actor, $assignee, false);

                    $created++;
                    $assigneeId = (int) $assignee->getKey();
                    $assignments[$assigneeId] ??= [
                        'user' => $assignee,
                        'count' => 0,
                    ];
                    $assignments[$assigneeId]['count']++;
                }

                break;
            }

            if ($headers === null) {
                throw ValidationException::withMessages(['file' => 'File Excel không có dữ liệu.']);
            }

            foreach ($assignments as $assignment) {
                DataCenterNotificationSender::imported($assignment['user'], $actor, $assignment['count']);
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } finally {
            $reader->close();
        }
    }

    private static function validateHeaders(array $headers): void
    {
        $requiredColumns = [
            ['keys' => ['ho_ten_khach_hang', 'ho_ten', 'ho_va_ten', 'customer_name', 'ten_khach_hang'], 'label' => 'Họ tên khách hàng'],
            ['keys' => ['so_dien_thoai', 'sdt', 'phone'], 'label' => 'Số điện thoại'],
            ['keys' => ['uid_nguoi_xu_ly', 'assigned_uid'], 'label' => 'UID người xử lý'],
        ];

        $missing = [];

        foreach ($requiredColumns as $column) {
            if (array_intersect($column['keys'], $headers) === []) {
                $missing[] = $column['label'];
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'File thiếu cột bắt buộc: '.implode(', ', $missing).'. Vui lòng tải và sử dụng đúng file mẫu.',
            ]);
        }
    }

    private static function isEmpty(array $values): bool
    {
        return count(array_filter($values, fn (mixed $value): bool => filled($value))) === 0;
    }

    private static function findAssignee(User $actor, string $uid): ?User
    {
        if ($uid === '') {
            return null;
        }

        return DataCenterAccess::assignableUsers($actor)
            ->whereRaw('LOWER(uid) = ?', [Str::lower($uid)])
            ->first();
    }

    private static function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (filled($row[$key] ?? null)) {
                return $row[$key];
            }
        }

        return null;
    }

    private static function key(string $value): string
    {
        return Str::of($value)
            ->replace("\xEF\xBB\xBF", '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private static function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private static function nullable(mixed $value): ?string
    {
        $value = self::text($value);

        return $value === '' ? null : $value;
    }

    private static function date(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, self::text($value))->format('Y-m-d');
            } catch (Throwable) {
            }
        }

        return null;
    }
}
