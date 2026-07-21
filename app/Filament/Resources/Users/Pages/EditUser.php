<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Pages\Concerns\InteractsWithUserMailbox;
use App\Models\User;
use App\Services\StalwartMailService;
use App\Support\RoleHierarchy;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    use InteractsWithUserMailbox;

    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Sửa người dùng';
    }

    public function getBreadcrumb(): string
    {
        return 'Sửa';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...$this->mailboxActions(),
                Action::make('regenerateLoginPassword')
                    ->icon(Heroicon::OutlinedKey)
                    ->label('Tạo lại mật khẩu mới')
                    ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false)
                    ->modalHeading('Tạo lại mật khẩu đăng nhập')
                    ->modalDescription(fn (): string => $this->getRecord()->name.' - '.($this->getRecord()->uid ?: $this->getRecord()->username))
                    ->modalSubmitActionLabel('Tạo lại mật khẩu')
                    ->modalCancelActionLabel('Hủy')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('new_password')
                            ->label('Mật khẩu mới')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rules([\Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()])
                            ->same('new_password_confirmation')
                            ->validationMessages(['same' => 'Xác nhận mật khẩu mới không khớp.']),
                        \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Xác nhận mật khẩu mới')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->getRecord()->forceFill([
                            'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
                        ])->save();
                        app(StalwartMailService::class)
                            ->scheduleCredentialSync($this->getRecord(), $data['new_password']);
                        Notification::make()->title('Đã tạo lại mật khẩu đăng nhập')->success()->send();
                    }),
                Action::make('saveUser')
                    ->icon(Heroicon::OutlinedCheck)
                    ->label('Lưu thay đổi')
                    ->action(fn () => $this->save()),
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye)
                    ->label('Xem người dùng'),
                Action::make('markDeleted')
                    ->icon(Heroicon::OutlinedTrash)
                    ->label('Xóa người dùng')
                    ->color('danger')
                    ->visible(fn (): bool => $this->getRecord()->employment_status !== User::STATUS_DELETED && (auth()->user()?->can('delete', $this->getRecord()) ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Xóa người dùng')
                    ->modalDescription('Người dùng sẽ được chuyển sang trạng thái Đã xoá, UID và Employee Code sẽ bị thu hồi. Dữ liệu hồ sơ vẫn được lưu lại.')
                    ->modalSubmitActionLabel('Xóa')
                    ->modalCancelActionLabel('Hủy')
                    ->action(function (): void {
                        $this->getRecord()->markAccessDeleted();

                        Notification::make()
                            ->title('Đã chuyển người dùng sang trạng thái Đã xoá')
                            ->success()
                            ->send();

                        $this->redirect(UserResource::getUrl('index'));
                    }),
                Action::make('reissueAccessCodes')
                    ->icon(Heroicon::OutlinedKey)
                    ->label('Tái cấp mã truy cập và Code')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->hasRole('Admin') && $this->getRecord()->employment_status === User::STATUS_DELETED)
                    ->requiresConfirmation()
                    ->modalHeading('Tái cấp mã truy cập')
                    ->modalDescription('Hệ thống sẽ cấp UID và Employee Code mới, đồng thời chuyển người dùng về trạng thái Hoạt động.')
                    ->modalSubmitActionLabel('Tái cấp')
                    ->modalCancelActionLabel('Hủy')
                    ->action(function (): void {
                        $record = $this->getRecord();
                        $record->reissueAccessCodes();

                        Notification::make()
                            ->title('Đã tái cấp UID và Employee Code')
                            ->body($record->uid.' / '.$record->employee_code)
                            ->success()
                            ->send();

                        $this->redirect(UserResource::getUrl('view', ['record' => $record]));
                    }),
                Action::make('cancel')
                    ->icon(Heroicon::OutlinedXMark)
                    ->label('Hủy')
                    ->color('gray')
                    ->url(UserResource::getUrl('index')),
            ])
                ->button()
                ->label('Hành động')
                ->icon(Heroicon::EllipsisHorizontal),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $role = $this->form->getRawState()['roles'] ?? $record->roles()->value('name');

        if (! RoleHierarchy::canUseRoleOnEdit(auth()->user(), $record, $role)) {
            throw ValidationException::withMessages([
                'roles' => 'Bạn không được phép gán vai trò này.',
            ]);
        }

        $data = RoleHierarchy::sanitizeProtectedUpdateData($data, $record, auth()->user());

        if (($data['employment_status'] ?? null) === User::STATUS_DELETED) {
            $data['uid'] = null;
            $data['employee_code'] = null;
        }

        $data = RoleHierarchy::normalizeManagerFields($data, auth()->user(), $role);

        if (auth()->id() !== $record->getKey()) {
            RoleHierarchy::validateManagerFields($data, auth()->user(), $role);
        }

        return UserResource::normalizeSalesCodes($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

}
