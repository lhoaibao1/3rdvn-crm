<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use App\Models\User;
use App\Services\StalwartMailService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

trait InteractsWithUserMailbox
{
    /** @return array<Action> */
    protected function mailboxActions(): array
    {
        return [
            Action::make('openMailbox')
                ->label('Mở 3RDVN Mail')
                ->icon(Heroicon::OutlinedEnvelopeOpen)
                ->url(fn (): string => (string) config('services.stalwart.webmail_url'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->getRecord()->mail_account_id)
                    && ($this->isMailAdministrator() || auth()->id() === $this->getRecord()->getKey())),
            Action::make('provisionMailbox')
                ->label('Cấp hộp thư')
                ->icon(Heroicon::OutlinedEnvelope)
                ->visible(fn (): bool => $this->isMailAdministrator()
                    && blank($this->getRecord()->mail_account_id)
                    && $this->getRecord()->employment_status === User::STATUS_ACTIVE)
                ->modalHeading('Cấp hộp thư doanh nghiệp')
                ->modalSubmitActionLabel('Cấp hộp thư')
                ->modalCancelActionLabel('Hủy')
                ->schema($this->mailboxCredentialSchema(true))
                ->action(function (array $data, StalwartMailService $mail): void {
                    $record = $this->getRecord();

                    try {
                        $mailbox = $mail->provision($record, $data['local_part'], $data['mail_password'], (int) $data['quota_mb']);
                        $record->forceFill([
                            'mail_address' => $mailbox['address'],
                            'mail_account_id' => $mailbox['id'],
                            'mail_status' => User::MAIL_STATUS_ACTIVE,
                            'mail_quota_mb' => (int) $data['quota_mb'],
                            'mail_provisioned_at' => now(),
                        ])->save();
                        $this->mailboxSucceeded('Đã cấp hộp thư '.$mailbox['address']);
                    } catch (Throwable $exception) {
                        $this->mailboxFailed($exception);
                    }
                }),
            Action::make('resetMailboxPassword')
                ->label('Đặt lại mật khẩu mail')
                ->icon(Heroicon::OutlinedKey)
                ->visible(fn (): bool => $this->isMailAdministrator()
                    && $this->getRecord()->mail_status === User::MAIL_STATUS_ACTIVE)
                ->modalHeading('Đặt lại mật khẩu hộp thư')
                ->modalDescription(fn (): string => (string) $this->getRecord()->mail_address)
                ->modalSubmitActionLabel('Đặt lại mật khẩu')
                ->modalCancelActionLabel('Hủy')
                ->schema($this->passwordSchema())
                ->action(function (array $data, StalwartMailService $mail): void {
                    try {
                        $mail->resetPassword($this->getRecord(), $data['mail_password']);
                        $this->mailboxSucceeded('Đã đặt lại mật khẩu hộp thư');
                    } catch (Throwable $exception) {
                        $this->mailboxFailed($exception);
                    }
                }),
            Action::make('suspendMailbox')
                ->label('Khóa hộp thư')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('danger')
                ->visible(fn (): bool => $this->isMailAdministrator()
                    && $this->getRecord()->mail_status === User::MAIL_STATUS_ACTIVE)
                ->requiresConfirmation()
                ->modalHeading('Khóa hộp thư')
                ->modalDescription('Người dùng sẽ không thể đăng nhập hộp thư cho đến khi được mở khóa.')
                ->modalSubmitActionLabel('Khóa hộp thư')
                ->modalCancelActionLabel('Hủy')
                ->action(function (StalwartMailService $mail): void {
                    try {
                        $mail->suspend($this->getRecord());
                        $this->getRecord()->forceFill(['mail_status' => User::MAIL_STATUS_SUSPENDED])->save();
                        $this->mailboxSucceeded('Đã khóa hộp thư');
                    } catch (Throwable $exception) {
                        $this->mailboxFailed($exception);
                    }
                }),
            Action::make('activateMailbox')
                ->label('Mở khóa hộp thư')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->visible(fn (): bool => $this->isMailAdministrator()
                    && $this->getRecord()->mail_status === User::MAIL_STATUS_SUSPENDED)
                ->modalHeading('Mở khóa hộp thư')
                ->modalDescription('Đặt mật khẩu mới để kích hoạt lại hộp thư.')
                ->modalSubmitActionLabel('Mở khóa')
                ->modalCancelActionLabel('Hủy')
                ->schema($this->passwordSchema())
                ->action(function (array $data, StalwartMailService $mail): void {
                    try {
                        $mail->activate($this->getRecord(), $data['mail_password']);
                        $this->getRecord()->forceFill(['mail_status' => User::MAIL_STATUS_ACTIVE])->save();
                        $this->mailboxSucceeded('Đã mở khóa hộp thư');
                    } catch (Throwable $exception) {
                        $this->mailboxFailed($exception);
                    }
                }),
        ];
    }

    /** @return array<TextInput> */
    private function mailboxCredentialSchema(bool $includeLocalPart): array
    {
        $schema = [];

        if ($includeLocalPart) {
            $schema[] = TextInput::make('local_part')
                ->label('Tên hộp thư')
                ->default(fn (): string => $this->suggestedMailboxLocalPart())
                ->suffix('@'.config('services.stalwart.domain'))
                ->required()
                ->maxLength(64)
                ->rules(['regex:/^[a-z0-9](?:[a-z0-9._-]{0,62}[a-z0-9])?$/'])
                ->validationMessages(['regex' => 'Chỉ dùng chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.']);
            $schema[] = TextInput::make('quota_mb')
                ->label('Dung lượng')
                ->numeric()
                ->default(2048)
                ->minValue(256)
                ->maxValue(51200)
                ->suffix('MB')
                ->required();
        }

        return [...$schema, ...$this->passwordSchema()];
    }

    /** @return array<TextInput> */
    private function passwordSchema(): array
    {
        return [
            TextInput::make('mail_password')
                ->label('Mật khẩu mới')
                ->password()
                ->revealable()
                ->required()
                ->rules([Password::min(12)->mixedCase()->numbers()->symbols()])
                ->same('mail_password_confirmation')
                ->validationMessages(['same' => 'Mật khẩu xác nhận không khớp.']),
            TextInput::make('mail_password_confirmation')
                ->label('Xác nhận mật khẩu')
                ->password()
                ->revealable()
                ->required(),
        ];
    }

    private function suggestedMailboxLocalPart(): string
    {
        $record = $this->getRecord();
        $source = $record->username ?: $record->employee_code ?: $record->uid ?: Str::before((string) $record->email, '@');
        $localPart = Str::lower(Str::ascii((string) $source));
        $localPart = preg_replace('/[^a-z0-9._-]+/', '.', $localPart) ?: '';
        $localPart = trim($localPart, '.-_');

        return $localPart !== '' ? $localPart : 'user'.$record->getKey();
    }

    private function isMailAdministrator(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    private function mailboxSucceeded(string $message): void
    {
        $this->getRecord()->refresh();
        Notification::make()->title($message)->success()->send();
    }

    private function mailboxFailed(Throwable $exception): void
    {
        report($exception);
        Notification::make()
            ->title('Không thể cập nhật hộp thư')
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }
}

