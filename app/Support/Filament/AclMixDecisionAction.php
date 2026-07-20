<?php

namespace App\Support\Filament;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;

class AclMixDecisionAction
{
    public static function make(string $name = 'processAclMix'): Action
    {
        return Action::make($name)
            ->label('Cập nhật trạng thái')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (Application $record): bool => AclMixWorkflow::canProcess(auth()->user(), $record))
            ->modalHeading(fn (Application $record): string => 'Xử lý '.$record->application_code)
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Lưu trạng thái')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (Application $record): array => self::form($record))
            ->action(function (Application $record, array $data): void {
                AclMixWorkflow::process($record, auth()->user(), $data);

                Notification::make()
                    ->title('Đã cập nhật hồ sơ')
                    ->body('Trạng thái mới: '.AclMixWorkflow::statusLabel($data['next_status'] ?? null))
                    ->success()
                    ->send();
            });
    }

    private static function form(Application $record): array
    {
        $note = Textarea::make('processing_note')->label('Ghi chú xử lý')->rows(3)->columnSpanFull();

        return match ($record->status) {
            AclMixWorkflow::PENDING_INITIAL_REVIEW => [
                Select::make('next_status')
                    ->label('Quyết định')
                    ->options([
                        AclMixWorkflow::SALE_COMPLETION => 'Khách hàng thoả mãn điều kiện',
                        AclMixWorkflow::REJECTED => 'Từ chối',
                    ])
                    ->required()->live(),
                Select::make('product')
                    ->label('Mã sản phẩm')
                    ->options(['ACL01' => 'ACL01', 'ACL02' => 'ACL02', 'ACL03' => 'ACL03', 'ACL04' => 'ACL04'])
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION),
                TextInput::make('pre_approved_amount')
                    ->label('Số tiền phê duyệt sơ bộ')->suffix('VNĐ')
                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))->stripCharacters('.')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION),
                TextInput::make('pre_approved_months')
                    ->label('Số tháng phê duyệt')->numeric()->suffix('tháng')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION),
                TextInput::make('pre_approved_interest_rate')
                    ->label('Lãi suất phê duyệt')->numeric()->suffix('%')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION),
                $note,
            ],
            AclMixWorkflow::SALE_COMPLETION, AclMixWorkflow::RETURNED_TO_SALE => [
                Hidden::make('next_status')->default(AclMixWorkflow::UNDERWRITING),
                Placeholder::make('transition')->label('Chuyển bước')->content('Gửi hồ sơ sang Đang thẩm định'),
                $note,
            ],
            AclMixWorkflow::UNDERWRITING => [
                Select::make('next_status')
                    ->label('Trạng thái tiếp theo')
                    ->options([
                        AclMixWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
                        AclMixWorkflow::AWAITING_CONTRACT => 'Chờ khách hàng ký hợp đồng',
                        AclMixWorkflow::REJECTED => 'Từ chối',
                    ])->required(),
                $note,
            ],
            AclMixWorkflow::AWAITING_CONTRACT => [
                Select::make('next_status')
                    ->label('Trạng thái tiếp theo')
                    ->options([
                        AclMixWorkflow::COMPLETED => 'Hoàn thành',
                        AclMixWorkflow::RETURNED_TO_SALE => 'Trả về Sale',
                        AclMixWorkflow::REJECTED => 'Từ chối',
                    ])->required()->live(),
                TextInput::make('contract_number')
                    ->label('Số hợp đồng')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::COMPLETED)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::COMPLETED)
                    ->maxLength(120),
                $note,
            ],
            AclMixWorkflow::REJECTED => [
                Hidden::make('next_status')->default(AclMixWorkflow::RETURNED_TO_SALE),
                Placeholder::make('transition')->label('Khôi phục hồ sơ')->content('Trả hồ sơ về Sale để cập nhật thông tin'),
                $note,
            ],
            default => [],
        };
    }
}
