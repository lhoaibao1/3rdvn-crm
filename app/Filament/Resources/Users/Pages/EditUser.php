<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\InteractsWithUserMailbox;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Filament\UserPasswordResetAction;
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
                UserPasswordResetAction::make(),
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
            ])
                ->button()
                ->label('Hành động')
                ->icon(Heroicon::EllipsisHorizontal),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Lưu thay đổi')
            ->icon(Heroicon::OutlinedCheck);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $data = UserForm::normalizeDateFields($data);
        $role = $this->form->getRawState()['roles'] ?? $record->roles()->value('name');
        $data = UserForm::normalizeTeamAssignment($data, $role);

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
