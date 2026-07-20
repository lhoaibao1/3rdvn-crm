<?php

namespace App\Support\Filament;

use App\Models\Lead;
use App\Support\Applications\LeadDecisionProcessor;
use App\Support\HotLeads\HotLeadConverter;
use App\Support\Permissions\LeadAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class LeadDecisionAction
{
    public static function make(?callable $recordResolver = null): Action
    {
        return Action::make('processLead')
            ->label('Xử lý hồ sơ')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (?Lead $record = null): bool => self::canProcess($recordResolver ? $recordResolver($record) : $record))
            ->modalHeading('Xử lý hồ sơ Lead')
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-process-modal'])
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Lưu quyết định')
            ->modalCancelActionLabel('Hủy')
            ->fillForm(fn (?Lead $record = null): array => self::initialData($recordResolver ? $recordResolver($record) : $record))
            ->form(fn (?Lead $record = null): array => self::form($recordResolver ? $recordResolver($record) : $record))
            ->action(function (array $data, ?Lead $record = null) use ($recordResolver): void {
                $lead = $recordResolver ? $recordResolver($record) : $record;

                if (! $lead instanceof Lead || ! auth()->user()) {
                    return;
                }

                $application = DB::transaction(function () use ($lead, $data) {
                    if (HotLeadConverter::isPromotedToLead($lead)) {
                        $lead = HotLeadConverter::moveToProject(
                            $lead,
                            auth()->user(),
                            $data['target_sales_project_id'] ?? null,
                        );
                    }

                    if ($lead->salesProject?->slug === 'lotte-finance' && array_key_exists('decision_result', $data)) {
                        $data = self::normalizeLotteDecisionData($data);
                    }

                    return LeadDecisionProcessor::process($lead, auth()->user(), $data);
                });

                Notification::make()
                    ->title('Đã lưu quyết định xử lý')
                    ->body($application ? 'Đã chuyển sang "'.($application->salesProject?->name ?? 'Dự án').'", vui lòng truy cập dự án để tiếp tục xử lý mã hồ sơ '.$application->application_code.'.' : null)
                    ->success()
                    ->send();
            });
    }

    private static function canProcess(?Lead $lead): bool
    {
        return $lead instanceof Lead
            && auth()->user() !== null
            && LeadAccess::canProcess(auth()->user(), $lead)
            && blank($lead->converted_at)
            && ! in_array($lead->status, ['Từ chối', 'Khách hàng bị trùng'], true)
            && ! $lead->trashed();
    }

    private static function initialData(?Lead $lead): array
    {
        $review = is_array($lead?->payload) ? ($lead->payload['review'] ?? []) : [];

        return [
            'status' => in_array($lead?->status, ['Từ chối', 'Khách hàng bị trùng', 'Khách hàng thoả mãn điều kiện'], true) ? $lead->status : null,
            'target_sales_project_id' => data_get($lead?->payload, 'workflow.target_sales_project_id'),
            'application_code' => $review['application_code'] ?? $lead?->application?->application_code,
            'decision_result' => $review['decision_result'] ?? ($lead?->status === 'Khách hàng thoả mãn điều kiện' ? 'pass' : null),
            'product' => $review['product'] ?? null,
            'pre_approved_amount' => isset($review['pre_approved_amount']) ? number_format((int) $review['pre_approved_amount'], 0, ',', '.') : null,
            'pre_approved_months' => $review['pre_approved_months'] ?? null,
            'pre_approved_interest_rate' => $review['pre_approved_interest_rate'] ?? null,
            'review_note' => $review['review_note'] ?? $lead?->note,
        ];
    }

    private static function form(?Lead $lead): array
    {
        if ($lead instanceof Lead && HotLeadConverter::isPromotedToLead($lead)) {
            return self::promotedHotLeadForm();
        }

        if ($lead?->salesProject?->slug === 'lotte-finance') {
            return self::lotteFinanceForm();
        }

        if ($lead?->salesProject?->slug === 'cbp') {
            return self::cbpForm();
        }

        return [
            Select::make('status')
                ->label('Quyết định')
                ->options([
                    'Khách hàng thoả mãn điều kiện' => 'Khách hàng thoả mãn điều kiện',
                    'Từ chối' => 'Từ chối',
                    'Khách hàng bị trùng' => 'Khách hàng bị trùng',
                ])
                ->required()
                ->live()
                ->native(false),
            TextInput::make('application_code')
                ->label('Mã hồ sơ/Application')
                ->required(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->maxLength(120),
            Select::make('product')
                ->label('Sản phẩm')
                ->options([
                    'ACL01' => 'ACL01',
                    'ACL02' => 'ACL02',
                    'ACL03' => 'ACL03',
                    'ACL04' => 'ACL04',
                ])
                ->native(false)
                ->required(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
            TextInput::make('pre_approved_amount')
                ->label('Số tiền phê duyệt sơ bộ')
                ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                ->stripCharacters('.')
                ->suffix('VNĐ')
                ->required(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
            TextInput::make('pre_approved_months')
                ->label('Số tháng phê duyệt')
                ->numeric()
                ->suffix('tháng')
                ->required(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
            TextInput::make('pre_approved_interest_rate')
                ->label('Lãi suất phê duyệt')
                ->numeric()
                ->suffix('%')
                ->required(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện'),
            Textarea::make('review_note')
                ->label('Ghi chú quyết định')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    private static function promotedHotLeadForm(): array
    {
        return [
            Select::make('target_sales_project_id')
                ->label('Dự án chuyển đến')
                ->options(fn (): array => LeadAccess::projectOptions(auth()->user()))
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),
            Select::make('status')
                ->label('Quyết định')
                ->options([
                    'Khách hàng thoả mãn điều kiện' => 'Khách hàng thoả mãn điều kiện',
                    'Từ chối' => 'Từ chối',
                    'Khách hàng bị trùng' => 'Khách hàng bị trùng',
                ])
                ->required()
                ->live()
                ->native(false),
            TextInput::make('application_code')
                ->label('Mã hồ sơ/Application')
                ->required(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->visible(fn (Get $get): bool => $get('status') === 'Khách hàng thoả mãn điều kiện')
                ->maxLength(120),
            Textarea::make('review_note')
                ->label('Ghi chú quyết định')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    private static function lotteFinanceForm(): array
    {
        return [
            Select::make('decision_result')
                ->label('Quyết định')
                ->options([
                    'pass' => 'Pass',
                    'fail' => 'Không pass',
                ])
                ->required()
                ->live()
                ->native(false),
            Placeholder::make('blacklist_check_display')
                ->label('Kiểm tra thông tin Blacklist')
                ->content(fn (Get $get): string => filled($get('decision_result')) ? 'Pass' : '-'),
            Placeholder::make('existing_check_display')
                ->label('Kiểm tra thông tin tồn tại')
                ->content(fn (Get $get): string => match ($get('decision_result')) {
                    'pass' => 'Pass',
                    'fail' => 'Khách hàng đã có hồ sơ tồn tại <=45 ngày',
                    default => '-',
                }),
            Placeholder::make('exception_check_display')
                ->label('Kiểm tra Khách hàng ngoại lệ')
                ->content(fn (Get $get): string => filled($get('decision_result')) ? 'Khách hàng không nằm trong danh sách ngoại lệ' : '-'),
            Placeholder::make('b11t_check_display')
                ->label('Kiểm tra thông tin B11T')
                ->content(fn (Get $get): string => filled($get('decision_result')) ? 'Pass' : '-'),
            TextInput::make('application_code')
                ->label('Mã hồ sơ/Application')
                ->required(fn (Get $get): bool => $get('decision_result') === 'pass')
                ->visible(fn (Get $get): bool => $get('decision_result') === 'pass')
                ->maxLength(120),
            Textarea::make('review_note')
                ->label('Ghi chú quyết định')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    private static function normalizeLotteDecisionData(array $data): array
    {
        $isPass = ($data['decision_result'] ?? null) === 'pass';

        $data['status'] = $isPass ? 'Khách hàng thoả mãn điều kiện' : 'Từ chối';
        $data['decision_result_label'] = $isPass ? 'Pass' : 'Không pass';
        $data['blacklist_check'] = 'Pass';
        $data['existing_check'] = $isPass ? 'Pass' : 'Khách hàng đã có hồ sơ tồn tại <=45 ngày';
        $data['exception_check'] = 'Khách hàng không nằm trong danh sách ngoại lệ';
        $data['b11t_check'] = 'Pass';

        if (! $isPass) {
            $data['application_code'] = null;
        }

        return $data;
    }

    private static function cbpForm(): array
    {
        return [
            Select::make('status')
                ->label('Quyết định')
                ->options([
                    'Khách hàng thoả mãn điều kiện' => 'Khách hàng thoả mãn điều kiện',
                    'Khách hàng bị trùng' => 'Khách hàng bị trùng',
                    'Từ chối' => 'Từ chối',
                ])
                ->required()
                ->native(false),
            Textarea::make('review_note')
                ->label('Ghi chú')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }
}
