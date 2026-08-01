<?php

namespace App\Support;

use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

class LosApplicationPresenter
{
    public static function make(Application $application): array
    {
        $fields = (array) data_get($application->payload, 'fields', []);
        $legacy = (array) data_get($application->payload, 'module_fields', []);
        $review = (array) data_get($application->payload, 'review', []);
        $projectSlug = (string) ($application->salesProject?->slug ?? '');
        $projectName = self::firstFilled([$application->salesProject?->name]);
        $applicationCode = self::firstFilled([
            $application->application_code,
            $review['application_code'] ?? null,
        ]);
        $applicantName = self::firstFilled([
            $application->applicant_name,
            $fields['customer_name'] ?? null,
            $legacy['customer_name'] ?? null,
        ]);
        $identityNumber = self::firstFilled([
            $application->identity_number,
            $fields['identity_number'] ?? null,
            $fields['cccd'] ?? null,
            $fields['cmnd'] ?? null,
            $legacy['cccd'] ?? null,
            $legacy['cmnd'] ?? null,
        ]);
        $product = self::firstFilled([
            $fields['scheme_product'] ?? null,
            $fields['product_name'] ?? null,
            $fields['product'] ?? null,
            $review['product'] ?? null,
            $fields['product_code'] ?? null,
        ]);
        $scheme = self::firstFilled([
            $fields['scheme_code'] ?? null,
            $fields['scheme_name'] ?? null,
        ]);
        $loanAmount = self::money([
            $fields['loan_amount'] ?? null,
            $fields['combo_loan_amount'] ?? null,
            $review['loan_amount'] ?? null,
            $review['pre_approved_amount'] ?? null,
        ]);
        $creator = self::firstFilled([
            $application->createdBy?->name,
            $application->createdBy?->uid,
            $application->createdBy?->employee_code,
        ]);
        $creatorCompact = collect([
            $application->createdBy?->name,
            $application->createdBy?->uid,
            $application->createdBy?->employee_code,
        ])->filter(fn (mixed $value): bool => filled($value))->unique()->implode(' · ') ?: '-';
        $statusLabel = self::statusLabel($application->status, $projectSlug);
        $statusTone = self::statusTone($application->status, $projectSlug);

        return [
            'id' => $application->getKey(),
            'application_code' => $applicationCode,
            'project' => $projectName,
            'applicant_name' => $applicantName,
            'identity_number' => $identityNumber,
            'product' => $product,
            'scheme' => $scheme,
            'loan_amount' => $loanAmount,
            'creator' => $creator,
            'created_at' => self::dateTime($application->created_at),
            'updated_at' => self::dateTime($application->updated_at),
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'summary_fields' => self::summaryFields(
                application: $application,
                fields: $fields,
                legacy: $legacy,
                review: $review,
                projectSlug: $projectSlug,
                projectName: $projectName,
                applicationCode: $applicationCode,
                applicantName: $applicantName,
                identityNumber: $identityNumber,
                product: $product,
                scheme: $scheme,
                loanAmount: $loanAmount,
                creator: $creator,
            ),
            'application_fields' => self::applicationFields(
                application: $application,
                fields: $fields,
                legacy: $legacy,
                review: $review,
                projectSlug: $projectSlug,
                applicationCode: $applicationCode,
                applicantName: $applicantName,
                product: $product,
                scheme: $scheme,
                loanAmount: $loanAmount,
                creatorCompact: $creatorCompact,
                statusLabel: $statusLabel,
                statusTone: $statusTone,
            ),
        ];
    }

    private static function applicationFields(
        Application $application,
        array $fields,
        array $legacy,
        array $review,
        string $projectSlug,
        string $applicationCode,
        string $applicantName,
        string $product,
        string $scheme,
        ?int $loanAmount,
        string $creatorCompact,
        string $statusLabel,
        string $statusTone,
    ): array {
        $items = [
            self::field('Mã hồ sơ', $applicationCode),
            self::field('Trạng thái', $statusLabel, $statusTone),
            self::field('Dự án', self::firstFilled([$application->salesProject?->name])),
            self::field('Khách hàng', $applicantName),
        ];

        if ($projectSlug === 'lotte-finance') {
            array_push(
                $items,
                self::field('Scheme', $scheme),
                self::field('Sản phẩm', $product),
                self::field('Số tiền vay', self::moneyLabel($loanAmount)),
                self::field('Hạn mức tối đa', self::moneyLabel(self::money([$review['maximum_limit'] ?? null]))),
                self::field('Số tiền được phê duyệt', self::moneyLabel(self::money([$review['approved_amount'] ?? null]))),
                self::field('Thời gian Approval', self::payloadDate($review['approved_at'] ?? null)),
                self::field('Lãi suất', self::percentage([
                    $review['estimated_interest_rate'] ?? null,
                    $fields['scheme_interest_rate'] ?? null,
                ])),
                self::field(
                    'Pre-Check',
                    self::firstFilled([$review['decision'] ?? null, 'Chờ xử lý']),
                    self::decisionTone($review['decision'] ?? null),
                ),
                self::field('Thời gian Pre-Check', self::payloadDate($review['reviewed_at'] ?? null)),
            );

            foreach ([
                'blacklist_check' => 'Blacklist',
                'b11t_check' => 'B11T',
                'aml_check' => 'AML',
                'pcb_check' => 'PCB',
                'lf_grade' => 'LF Grade',
                'ml_grade' => 'ML Grade',
            ] as $key => $label) {
                $items[] = self::field(
                    $label,
                    self::firstFilled([$review[$key] ?? null]),
                    self::decisionTone($review[$key] ?? null),
                );
            }
        } elseif ($projectSlug === 'acl-mix') {
            if (filled($review['product'] ?? null)) {
                $items[] = self::field('Sản phẩm', trim((string) $review['product']));
            }

            $preApprovedAmount = self::money([$review['pre_approved_amount'] ?? null]);

            if (! is_null($preApprovedAmount)) {
                $items[] = self::field('Số tiền phê duyệt sơ bộ', self::moneyLabel($preApprovedAmount));
            }

            if (filled($review['pre_approved_months'] ?? null)) {
                $items[] = self::field('Thời hạn phê duyệt', trim((string) $review['pre_approved_months']).' tháng');
            }

            if (filled($review['pre_approved_interest_rate'] ?? null)) {
                $items[] = self::field('Lãi suất phê duyệt', self::percentage([$review['pre_approved_interest_rate']]));
            }

            if (filled($review['decision'] ?? null)) {
                $items[] = self::field('Kết quả kiểm tra', trim((string) $review['decision']));
            }

            if (filled($review['reviewed_at'] ?? null)) {
                $items[] = self::field('Thời gian kiểm tra', self::payloadDate($review['reviewed_at']));
            }

            $contractNumber = data_get($application->payload, 'workflow.contract_number');

            if (filled($contractNumber)) {
                $items[] = self::field('Số hợp đồng', trim((string) $contractNumber));
            }
        } else {
            array_push($items, ...self::projectSpecificFields($application, $fields, $legacy));
        }

        array_push(
            $items,
            self::field('NVKD', $creatorCompact),
            self::field('Team', self::firstFilled([self::relationAttribute($application, 'team', 'name')])),
            self::field('Team Leader', self::firstFilled([self::relationAttribute($application, 'teamLeader', 'name')])),
            self::field('Người xử lý', self::firstFilled([self::relationAttribute($application, 'assignedSale', 'name')])),
            self::field('Ngày tạo', self::dateTime($application->created_at)),
            self::field('Cập nhật', self::dateTime($application->updated_at)),
        );

        if ($projectSlug === 'lotte-finance') {
            foreach ([
                'review_note' => 'Ghi chú Pre-Check',
                'approval_note' => 'Ghi chú Approval',
            ] as $key => $label) {
                if (filled($review[$key] ?? null)) {
                    $items[] = self::field($label, trim((string) $review[$key]), wide: true);
                }
            }

            $fileNote = self::rawFirstFilled([$application->note, $fields['note'] ?? null]);

            if (filled($fileNote)) {
                $items[] = self::field('Ghi chú hồ sơ', trim((string) $fileNote), wide: true);
            }
        } elseif ($projectSlug === 'acl-mix') {
            if (filled($review['review_note'] ?? null)) {
                $items[] = self::field('Ghi chú kiểm tra', trim((string) $review['review_note']), wide: true);
            }

            if (filled($application->note) && $application->note !== ($review['review_note'] ?? null)) {
                $items[] = self::field('Ghi chú hồ sơ', trim((string) $application->note), wide: true);
            }
        } elseif (filled($application->note)) {
            $items[] = self::field('Ghi chú', trim((string) $application->note), wide: true);
        }

        return $items;
    }

    private static function summaryFields(
        Application $application,
        array $fields,
        array $legacy,
        array $review,
        string $projectSlug,
        string $projectName,
        string $applicationCode,
        string $applicantName,
        string $identityNumber,
        string $product,
        string $scheme,
        ?int $loanAmount,
        string $creator,
    ): array {
        $items = [
            self::field('Mã hồ sơ', $applicationCode),
            self::field('Dự án', $projectName),
            self::field('Họ tên', $applicantName),
            self::field('CCCD/CMND', $identityNumber),
        ];

        if ($projectSlug === 'lotte-finance') {
            array_push(
                $items,
                self::field('Sản phẩm', $product),
                self::field('Scheme', $scheme),
                self::field('Số tiền vay', self::moneyLabel($loanAmount)),
            );
        } elseif ($projectSlug === 'acl-mix') {
            if (filled($review['product'] ?? null)) {
                $items[] = self::field('Sản phẩm', trim((string) $review['product']));
            }

            $preApprovedAmount = self::money([$review['pre_approved_amount'] ?? null]);

            if (! is_null($preApprovedAmount)) {
                $items[] = self::field('Số tiền phê duyệt sơ bộ', self::moneyLabel($preApprovedAmount));
            }

            if (filled($review['pre_approved_months'] ?? null)) {
                $items[] = self::field('Thời hạn phê duyệt', trim((string) $review['pre_approved_months']).' tháng');
            }

            if (filled($review['pre_approved_interest_rate'] ?? null)) {
                $items[] = self::field('Lãi suất phê duyệt', self::percentage([$review['pre_approved_interest_rate']]));
            }
        } else {
            array_push($items, ...array_slice(self::projectSpecificFields($application, $fields, $legacy), 0, 4));
        }

        array_push(
            $items,
            self::field('Người tạo', $creator),
            self::field('Ngày tạo', self::dateTime($application->created_at)),
            self::field('Ngày cập nhật', self::dateTime($application->updated_at)),
        );

        return $items;
    }

    private static function projectSpecificFields(Application $application, array $fields, array $legacy): array
    {
        $project = $application->relationLoaded('salesProject')
            ? $application->getRelation('salesProject')
            : null;
        $items = [];
        $seen = [];
        $excluded = array_fill_keys([
            'customer_name', 'applicant_name', 'lead_name', 'phone', 'identity_number',
            'cccd', 'cmnd', 'status', 'application_code',
        ], true);

        foreach ([
            [(array) ($project?->lead_form_schema ?? []), $fields],
            [(array) ($project?->module_form_schema ?? []), $legacy],
        ] as [$definitions, $values]) {
            foreach ($definitions as $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $key = str((string) ($definition['field_key'] ?? $definition['key'] ?? ''))->snake()->toString();

                if ($key === '' || isset($seen[$key]) || isset($excluded[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $displayKey = match ($key) {
                    'province_code' => 'province_name',
                    'district_code' => 'district_name',
                    'ward_code' => 'ward_name',
                    default => str_ends_with($key, '_province_code')
                        || str_ends_with($key, '_district_code')
                        || str_ends_with($key, '_ward_code')
                            ? substr($key, 0, -4).'name'
                            : $key,
                };
                $value = $values[$displayKey] ?? $values[$key] ?? null;

                if (! filled($value)) {
                    continue;
                }

                $label = filled($definition['label'] ?? null)
                    ? trim((string) $definition['label'])
                    : str($key)->replace('_', ' ')->title()->toString();
                $items[] = self::field($label, self::displayValue($value));
            }
        }

        return $items;
    }

    private static function displayValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if (is_array($value)) {
            return collect($value)->filter(fn (mixed $item): bool => filled($item))->map(fn (mixed $item): string => (string) $item)->implode(', ');
        }

        return trim((string) $value);
    }

    private static function field(string $label, string $value, ?string $tone = null, bool $wide = false): array
    {
        return compact('label', 'value', 'tone', 'wide');
    }

    private static function relationAttribute(Application $application, string $relation, string $attribute): mixed
    {
        if (! $application->relationLoaded($relation)) {
            return null;
        }

        $related = $application->getRelation($relation);

        return $related ? data_get($related, $attribute) : null;
    }

    private static function firstFilled(array $values): string
    {
        $value = self::rawFirstFilled($values);

        return filled($value) ? trim((string) $value) : '-';
    }

    private static function rawFirstFilled(array $values): mixed
    {
        return collect($values)->first(fn (mixed $candidate): bool => filled($candidate));
    }

    private static function money(array $values): ?int
    {
        $value = self::rawFirstFilled($values);
        $digits = preg_replace('/[^0-9-]+/', '', (string) $value) ?: '';

        return $digits !== '' ? (int) $digits : null;
    }

    private static function moneyLabel(?int $value): string
    {
        return is_null($value) ? '-' : number_format($value, 0, ',', '.').' VNĐ';
    }

    private static function percentage(array $values): string
    {
        $value = self::rawFirstFilled($values);

        if (! filled($value)) {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    }

    private static function payloadDate(mixed $value): string
    {
        if (! filled($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('H:i d/m/Y');
        } catch (Throwable) {
            return trim((string) $value);
        }
    }

    private static function dateTime(?CarbonInterface $value): string
    {
        return $value?->format('H:i d/m/Y') ?? '-';
    }

    private static function statusLabel(?string $status, string $projectSlug): string
    {
        return match ($projectSlug) {
            'lotte-finance' => LotteFinanceWorkflow::statusLabel($status),
            'acl-mix' => AclMixWorkflow::statusLabel($status),
            default => match ($status) {
                'processing' => 'Đang xử lý',
                'pending_approval' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối',
                default => $status ?: '-',
            },
        };
    }

    private static function statusTone(?string $status, string $projectSlug): string
    {
        if ($projectSlug === 'lotte-finance') {
            return LotteFinanceWorkflow::statusColor($status);
        }

        if ($projectSlug === 'acl-mix') {
            return AclMixWorkflow::statusColor($status);
        }

        return match ($status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'processing', 'pending_approval' => 'primary',
            default => 'gray',
        };
    }

    private static function decisionTone(mixed $decision): string
    {
        return match ($decision) {
            'Pass' => 'success',
            'Không Pass' => 'danger',
            default => 'gray',
        };
    }
}
