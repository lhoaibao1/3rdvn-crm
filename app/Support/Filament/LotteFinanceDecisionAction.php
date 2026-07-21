<?php

namespace App\Support\Filament;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Application;
use App\Support\Applications\LotteFinanceWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;

class LotteFinanceDecisionAction
{
    public static function make(string $name = 'processLotteFinance'): Action
    {
        return Action::make($name)
            ->label('Xử lý hồ sơ')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (Application $record): bool => LotteFinanceWorkflow::canProcess(auth()->user(), $record))
            ->modalHeading(fn (Application $record): string => 'Xử lý '.($record->application_code ?: $record->applicant_name))
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Cập nhật trạng thái')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (Application $record): array => self::form($record))
            ->action(function (Application $record, array $data): void {
                $application = LotteFinanceWorkflow::process($record, auth()->user(), $data);

                Notification::make()
                    ->title('Đã cập nhật hồ sơ Lotte Finance')
                    ->body('Trạng thái mới: '.LotteFinanceWorkflow::statusLabel($application->status))
                    ->success()
                    ->send();
            });
    }

    private static function form(Application $record): array
    {
        $note = Textarea::make('processing_note')
            ->label('Ghi chú xử lý')
            ->rows(3)
            ->columnSpanFull();

        if ($record->status === LotteFinanceWorkflow::PRE_CHECK) {
            return [
                Select::make('decision')
                    ->label('Quyết định Pre-Check')
                    ->options(['pass' => 'Pass', 'not_pass' => 'Không Pass'])
                    ->required()
                    ->live(),
                TextInput::make('application_code')
                    ->label('Mã hồ sơ')
                    ->default($record->application_code)
                    ->required()
                    ->maxLength(120),
                ...collect(['Blacklist', 'B11T', 'AML', 'PCB'])
                    ->map(fn (string $check): Placeholder => Placeholder::make('check_'.strtolower($check))
                        ->label('Kiểm tra '.$check)
                        ->content(fn (Get $get): string => match ($get('decision')) {
                            'pass' => 'Pass',
                            'not_pass' => 'Không Pass',
                            default => 'Chờ quyết định',
                        }))
                    ->all(),
                TextInput::make('lf_grade')
                    ->label('LF Grade')
                    ->visible(fn (Get $get): bool => $get('decision') === 'pass')
                    ->required(fn (Get $get): bool => $get('decision') === 'pass')
                    ->maxLength(50),
                TextInput::make('ml_grade')
                    ->label('ML Grade')
                    ->visible(fn (Get $get): bool => $get('decision') === 'pass')
                    ->required(fn (Get $get): bool => $get('decision') === 'pass')
                    ->maxLength(50),
                TextInput::make('maximum_limit')
                    ->label('Hạn mức tối đa')
                    ->suffix('VNĐ')
                    ->mask(RawJs::make('$money($input, ",", ".", 0)'))
                    ->stripCharacters('.')
                    ->visible(fn (Get $get): bool => $get('decision') === 'pass')
                    ->required(fn (Get $get): bool => $get('decision') === 'pass'),
                TextInput::make('estimated_interest_rate')
                    ->label('Lãi suất dự kiến')
                    ->numeric()
                    ->suffix('%')
                    ->visible(fn (Get $get): bool => $get('decision') === 'pass')
                    ->required(fn (Get $get): bool => $get('decision') === 'pass'),
                $note,
            ];
        }

        return [
            Select::make('next_status')
                ->label('Trạng thái tiếp theo')
                ->options(self::nextStatusOptions($record->status))
                ->required(),
            $note,
        ];
    }

    private static function nextStatusOptions(string $status): array
    {
        return match ($status) {
            LotteFinanceWorkflow::UW_CALL => [
                LotteFinanceWorkflow::UW_APPROVAL => 'UW Approval',
                LotteFinanceWorkflow::UW_FIELD => 'UW Field',
            ],
            LotteFinanceWorkflow::UW_APPROVAL => [
                LotteFinanceWorkflow::UW_FIELD => 'UW Field',
                LotteFinanceWorkflow::OP => 'OP',
            ],
            LotteFinanceWorkflow::UW_FIELD => [
                LotteFinanceWorkflow::UW_APPROVAL => 'UW Approval',
                LotteFinanceWorkflow::OP => 'OP',
            ],
            LotteFinanceWorkflow::OP => [LotteFinanceWorkflow::ESIGN => 'eSign'],
            LotteFinanceWorkflow::ESIGN => [LotteFinanceWorkflow::POST_APPROVAL => 'Post Approval'],
            default => [],
        };
    }
}
