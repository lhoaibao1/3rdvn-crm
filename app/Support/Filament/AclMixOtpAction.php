<?php

namespace App\Support\Filament;

use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class AclMixOtpAction
{
    public static function make(string $name = 'updateAclMixOtp'): Action
    {
        return Action::make($name)
            ->label('Cập nhật OTP')
            ->icon(Heroicon::OutlinedKey)
            ->color('info')
            ->visible(fn (Application $record): bool => AclMixWorkflow::canUpdateOtp(auth()->user(), $record))
            ->modalHeading(fn (Application $record): string => 'OTP '.($record->application_code ?: $record->applicant_name))
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-process-modal'])
            ->modalWidth('md')
            ->modalAutofocus(false)
            ->modalSubmitActionLabel('Lưu OTP')
            ->modalCancelActionLabel('Hủy')
            ->fillForm(fn (Application $record): array => [
                'otp' => data_get($record->payload, 'review.otp'),
            ])
            ->schema([
                TextInput::make('otp')
                    ->label('OTP')
                    ->helperText('Có thể lưu lại nhiều lần khi hồ sơ đang ở bước Đang kiểm tra.')
                    ->required()
                    ->maxLength(20),
            ])
            ->action(function (Application $record, array $data, mixed $livewire): void {
                $application = AclMixWorkflow::updateOtp($record, auth()->user(), (string) $data['otp']);

                if (method_exists($livewire, 'flushCachedTableRecords')) {
                    $livewire->flushCachedTableRecords();
                }

                if (property_exists($livewire, 'record') && $livewire->record instanceof Application && $livewire->record->is($application)) {
                    $livewire->record = $application->load([
                        'salesProject', 'assignedSale', 'createdBy', 'team', 'teamLeader',
                    ]);
                }

                Notification::make()
                    ->title('Đã lưu OTP')
                    ->body('OTP đã được cập nhật, trạng thái hồ sơ không thay đổi.')
                    ->success()
                    ->send();
            });
    }
}
