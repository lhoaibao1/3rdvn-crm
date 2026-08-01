<?php

namespace App\Support\Filament;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Support\AdminWorkflowOverride;
use App\Support\Applications\AclMixWorkflow;
use Filament\Actions\Action;
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
            ->label('Xử lý hồ sơ')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (Application $record): bool => AclMixWorkflow::canProcess(auth()->user(), $record))
            ->modalHeading(fn (Application $record): string => 'Xử lý '.($record->application_code ?: $record->applicant_name))
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-process-modal'])
            ->modalWidth('2xl')
            ->modalAutofocus(false)
            ->modalSubmitActionLabel('Chuyển bước')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (Application $record): array => self::form($record))
            ->action(function (Application $record, array $data, mixed $livewire): void {
                $application = AclMixWorkflow::process($record, auth()->user(), $data);

                self::refreshLivewireRecord($livewire, $application);

                Notification::make()
                    ->title('Đã cập nhật hồ sơ')
                    ->body('Trạng thái mới: '.AclMixWorkflow::statusLabel($application->status))
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
                    ->options(AclMixWorkflow::nextStatusOptions($record))
                    ->required()->live(),
                TextInput::make('application_code')
                    ->label('Mã hồ sơ')
                    ->default($record->application_code)
                    ->required(AdminWorkflowOverride::required())
                    ->maxLength(120),
                Select::make('product')
                    ->label('Mã sản phẩm')
                    ->options(['ACL01' => 'ACL01', 'ACL02' => 'ACL02', 'ACL03' => 'ACL03', 'ACL04' => 'ACL04'])
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION && AdminWorkflowOverride::required()),
                TextInput::make('pre_approved_amount')
                    ->label('Số tiền phê duyệt sơ bộ')->suffix('VNĐ')
                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))->stripCharacters('.')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION && AdminWorkflowOverride::required()),
                TextInput::make('pre_approved_months')
                    ->label('Số tháng phê duyệt')->numeric()->suffix('tháng')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION && AdminWorkflowOverride::required()),
                TextInput::make('pre_approved_interest_rate')
                    ->label('Lãi suất phê duyệt')->numeric()->suffix('%')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::SALE_COMPLETION && AdminWorkflowOverride::required()),
                $note,
            ],
            AclMixWorkflow::CALL_RECORDING => [
                Select::make('next_status')
                    ->label('Trạng thái tiếp theo')
                    ->options(AclMixWorkflow::nextStatusOptions($record))
                    ->required(),
                $note,
            ],
            AclMixWorkflow::UNDERWRITING => [
                Select::make('next_status')
                    ->label('Trạng thái tiếp theo')
                    ->options(AclMixWorkflow::nextStatusOptions($record))->required(),
                $note,
            ],
            AclMixWorkflow::AWAITING_CONTRACT => [
                Select::make('next_status')
                    ->label('Trạng thái tiếp theo')
                    ->options(AclMixWorkflow::nextStatusOptions($record))->required()->live(),
                TextInput::make('contract_number')
                    ->label('Số hợp đồng')
                    ->visible(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::COMPLETED)
                    ->required(fn (Get $get): bool => $get('next_status') === AclMixWorkflow::COMPLETED && AdminWorkflowOverride::required())
                    ->maxLength(120),
                $note,
            ],
            default => [],
        };
    }

    private static function refreshLivewireRecord(mixed $livewire, Application $application): void
    {
        if (! is_object($livewire)) {
            return;
        }

        if (method_exists($livewire, 'flushCachedTableRecords')) {
            $livewire->flushCachedTableRecords();
        }

        if (! property_exists($livewire, 'record')) {
            return;
        }

        $currentRecord = $livewire->record;

        if (! $currentRecord instanceof Application || ! $currentRecord->is($application)) {
            return;
        }

        $currentRecord->refresh();
        $currentRecord->load([
            'salesProject', 'assignedSale', 'createdBy', 'team', 'teamLeader',
        ]);
        $livewire->record = $currentRecord;

        if (method_exists($livewire, 'refreshFormData')) {
            $livewire->refreshFormData(['application_code', 'status', 'payload', 'note', 'updated_at']);
        }
    }
}
