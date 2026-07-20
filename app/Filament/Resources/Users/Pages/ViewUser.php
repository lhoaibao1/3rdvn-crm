<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Pages\Concerns\InteractsWithUserMailbox;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewUser extends ViewRecord
{
    use InteractsWithUserMailbox;

    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Người dùng';
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }


    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...$this->mailboxActions(),
                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->label('Cập nhật người dùng'),
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
}
