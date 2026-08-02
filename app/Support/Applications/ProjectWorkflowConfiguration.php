<?php

namespace App\Support\Applications;

use App\Models\SalesProject;

class ProjectWorkflowConfiguration
{
    public const MANUAL = 'manual';

    public const AUTOMATIC = 'automatic';

    public const SPECIAL = 'special';

    public const TERMINAL = 'terminal';

    public const LEGACY = 'legacy';

    /** @return array<int, string> */
    public static function supportedSlugs(): array
    {
        return ['acl-mix', 'lotte-finance'];
    }

    public static function supports(?string $projectSlug): bool
    {
        return in_array($projectSlug, self::supportedSlugs(), true);
    }

    /** @return array<int, array{status: string, label: string, next_statuses: array<int, string>, mode: string, note: string}> */
    public static function defaults(?string $projectSlug): array
    {
        return match ($projectSlug) {
            'acl-mix' => [
                self::step(AclMixWorkflow::PENDING_INITIAL_REVIEW, 'Chờ kiểm tra', [AclMixWorkflow::INELIGIBLE, AclMixWorkflow::OTP_REQUIRED], self::SPECIAL, 'Người xử lý chọn Không thoả điều kiện hoặc Yêu cầu OTP.'),
                self::step(AclMixWorkflow::OTP_REQUIRED, 'Đang kiểm tra', [AclMixWorkflow::CUSTOMER_CAPP], self::SPECIAL, 'Cập nhật OTP nhưng giữ nguyên trạng thái; chỉ chuyển bước sau khi đã có OTP.'),
                self::step(AclMixWorkflow::CUSTOMER_CAPP, 'Khách hàng thao tác CAPP', [AclMixWorkflow::SALE_COMPLETION, AclMixWorkflow::REJECTED], self::MANUAL, 'Khách hàng thoả mãn điều kiện tiếp tục workflow cũ; Từ chối sẽ đóng hồ sơ.'),
                self::step(AclMixWorkflow::INELIGIBLE, 'Không thoả điều kiện', [], self::TERMINAL, 'Điểm kết thúc tại bước kiểm tra ban đầu.'),
                self::step(AclMixWorkflow::SALE_COMPLETION, 'Chờ Sale hoàn thiện thông tin', [AclMixWorkflow::CALL_RECORDING], self::AUTOMATIC, 'Tự chuyển khi Sale lưu hoàn tất hồ sơ.'),
                self::step(AclMixWorkflow::CALL_RECORDING, 'Cuộc gọi ghi âm', [AclMixWorkflow::UNDERWRITING], self::MANUAL, 'Người xử lý xác nhận đã hoàn tất cuộc gọi.'),
                self::step(AclMixWorkflow::UNDERWRITING, 'Đang thẩm định', [AclMixWorkflow::RETURNED_TO_SALE, AclMixWorkflow::AWAITING_CONTRACT, AclMixWorkflow::REJECTED], self::MANUAL, 'Người xử lý chọn kết quả thẩm định.'),
                self::step(AclMixWorkflow::RETURNED_TO_SALE, 'Trả về Sale', [AclMixWorkflow::CALL_RECORDING], self::AUTOMATIC, 'Tự chuyển khi Sale cập nhật và gửi lại hồ sơ.'),
                self::step(AclMixWorkflow::AWAITING_CONTRACT, 'Chờ khách hàng ký hợp đồng', [AclMixWorkflow::COMPLETED, AclMixWorkflow::RETURNED_TO_SALE, AclMixWorkflow::REJECTED], self::MANUAL, 'Người xử lý cập nhật kết quả ký hợp đồng.'),
                self::step(AclMixWorkflow::COMPLETED, 'Hoàn thành', [], self::TERMINAL, 'Điểm kết thúc workflow.'),
                self::step(AclMixWorkflow::REJECTED, 'Từ chối', [], self::TERMINAL, 'Điểm kết thúc workflow.'),
            ],
            'lotte-finance' => [
                self::step(LotteFinanceWorkflow::PRE_CHECK, 'Pre-Check', [LotteFinanceWorkflow::SALE_COMPLETION, LotteFinanceWorkflow::REJECTED], self::SPECIAL, 'Pass chuyển sang Chờ Sale bổ sung; Không Pass kết thúc.'),
                self::step(LotteFinanceWorkflow::SALE_COMPLETION, 'Chờ Sale bổ sung thông tin', [LotteFinanceWorkflow::UW_CALL], self::AUTOMATIC, 'Tự chuyển khi Sale lưu hoàn tất hồ sơ.'),
                self::step(LotteFinanceWorkflow::RETURNED_TO_SALE, 'Trả về Sale', [LotteFinanceWorkflow::UW_CALL], self::AUTOMATIC, 'Tự chuyển khi Sale cập nhật và gửi lại hồ sơ.'),
                self::step(LotteFinanceWorkflow::UW_CALL, 'UW Call', [LotteFinanceWorkflow::UW_APPROVAL, LotteFinanceWorkflow::UW_REJECTED, LotteFinanceWorkflow::UW_FIELD, LotteFinanceWorkflow::RETURNED_TO_SALE], self::MANUAL, 'Người xử lý chọn kết quả UW Call.'),
                self::step(LotteFinanceWorkflow::UW_APPROVAL, 'UW Approval', [LotteFinanceWorkflow::ESIGN, LotteFinanceWorkflow::RETURNED_TO_SALE], self::MANUAL, 'Người xử lý chọn eSign hoặc trả Sale.'),
                self::step(LotteFinanceWorkflow::UW_REJECTED, 'UW Rej', [], self::TERMINAL, 'Điểm kết thúc workflow.'),
                self::step(LotteFinanceWorkflow::UW_FIELD, 'UW Field', [], self::TERMINAL, 'Điểm kết thúc workflow.'),
                self::step(LotteFinanceWorkflow::OP, 'OP (hồ sơ cũ)', [LotteFinanceWorkflow::ESIGN, LotteFinanceWorkflow::RETURNED_TO_SALE], self::LEGACY, 'Chỉ dùng để xử lý các hồ sơ OP đã tồn tại.'),
                self::step(LotteFinanceWorkflow::ESIGN, 'eSign', [LotteFinanceWorkflow::POST_APPROVAL, LotteFinanceWorkflow::RETURNED_TO_SALE], self::MANUAL, 'Người xử lý chọn Post Approval hoặc trả Sale.'),
                self::step(LotteFinanceWorkflow::POST_APPROVAL, 'Post Approval', [LotteFinanceWorkflow::DISBURSED, LotteFinanceWorkflow::RETURNED_TO_SALE], self::MANUAL, 'Người xử lý cập nhật kết quả giải ngân.'),
                self::step(LotteFinanceWorkflow::DISBURSED, 'Đã giải ngân', [], self::TERMINAL, 'Điểm kết thúc workflow.'),
                self::step(LotteFinanceWorkflow::REJECTED, 'Không Pass', [], self::TERMINAL, 'Điểm kết thúc workflow.'),
            ],
            default => [],
        };
    }

    /** @return array<int, array{status: string, label: string, next_statuses: array<int, string>, mode: string, note: string}> */
    public static function forProject(?SalesProject $project): array
    {
        if (! $project instanceof SalesProject) {
            return [];
        }

        $defaults = self::defaults($project->slug);
        $knownStatuses = collect($defaults)->pluck('status')->all();
        $configured = collect(is_array($project->workflow_schema) ? $project->workflow_schema : [])
            ->filter(fn (mixed $step): bool => is_array($step) && filled($step['status'] ?? null))
            ->keyBy('status');

        return collect($defaults)
            ->map(function (array $step) use ($configured, $knownStatuses): array {
                if (! in_array($step['mode'], [self::MANUAL, self::LEGACY], true)) {
                    return $step;
                }

                $saved = $configured->get($step['status']);

                if (! is_array($saved) || ! array_key_exists('next_statuses', $saved)) {
                    return $step;
                }

                $step['next_statuses'] = collect((array) $saved['next_statuses'])
                    ->filter(fn (mixed $status): bool => is_string($status)
                        && $status !== $step['status']
                        && in_array($status, $knownStatuses, true))
                    ->unique()
                    ->values()
                    ->all();

                return $step;
            })
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    public static function statusOptions(?string $projectSlug): array
    {
        return collect(self::defaults($projectSlug))
            ->mapWithKeys(fn (array $step): array => [$step['status'] => $step['label']])
            ->all();
    }

    /** @return array<string, string> */
    public static function nextStatusOptions(?SalesProject $project, string $currentStatus): array
    {
        $steps = $project instanceof SalesProject
            ? self::forProject($project)
            : [];

        $step = collect($steps)->firstWhere('status', $currentStatus);

        if (! is_array($step)) {
            return [];
        }

        $labels = collect($steps)->mapWithKeys(fn (array $item): array => [$item['status'] => $item['label']]);

        return collect($step['next_statuses'])
            ->mapWithKeys(fn (string $status): array => [$status => (string) ($labels[$status] ?? $status)])
            ->all();
    }

    public static function modeLabel(string $mode): string
    {
        return match ($mode) {
            self::MANUAL => 'Xử lý thủ công',
            self::AUTOMATIC => 'Tự động sau khi Sale lưu',
            self::SPECIAL => 'Quy tắc nghiệp vụ riêng',
            self::TERMINAL => 'Điểm kết thúc',
            self::LEGACY => 'Chỉ dành cho hồ sơ cũ',
            default => $mode,
        };
    }

    public static function normalizeForStorage(SalesProject $project): array
    {
        return collect(self::forProject($project))
            ->map(fn (array $step): array => [
                'status' => $step['status'],
                'next_statuses' => $step['next_statuses'],
            ])
            ->values()
            ->all();
    }

    /** @return array{status: string, label: string, next_statuses: array<int, string>, mode: string, note: string} */
    private static function step(string $status, string $label, array $nextStatuses, string $mode, string $note): array
    {
        return [
            'status' => $status,
            'label' => $label,
            'next_statuses' => $nextStatuses,
            'mode' => $mode,
            'note' => $note,
        ];
    }
}
