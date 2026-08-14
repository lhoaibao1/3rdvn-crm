<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Support\Applications\AclMixWorkflow;
use App\Support\Applications\LotteFinanceWorkflow;
use App\Support\Applications\RequestFeolApplicationSync;
use App\Support\Filament\AclMixDecisionAction;
use App\Support\Filament\AclMixOtpAction;
use App\Support\Filament\LotteFinanceDecisionAction;
use App\Support\Filament\RecordAssignAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getTitle(): string
    {
        return $this->record->applicant_name ?: $this->record->application_code;
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }

    protected function getHeaderActions(): array
    {
        return [
            AclMixOtpAction::make(),
            AclMixDecisionAction::make(),
            LotteFinanceDecisionAction::make(),
            RecordAssignAction::make('assignApplicationProcessor'),
            Action::make('requestFeolSync')
                ->label('Kiểm tra đối tác ngay')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info')
                ->visible(fn (Application $record): bool => $record->salesProject?->slug === 'fe-deeplink'
                    && (auth()->user()?->can('update', $record) ?? false))
                ->action(function (Application $record): void {
                    app(RequestFeolApplicationSync::class)->handle($record);
                    Notification::make()->title('Đã đưa hồ sơ vào hàng đợi kiểm tra')->success()->send();
                }),
            EditAction::make()
                ->label('Cập nhật thông tin')
                ->visible(fn (Application $record): bool => match ($record->salesProject?->slug) {
                    'acl-mix' => AclMixWorkflow::canEditData(auth()->user(), $record),
                    'lotte-finance' => LotteFinanceWorkflow::canEditData(auth()->user(), $record),
                    default => true,
                }),
            DeleteAction::make()
                ->label('Xóa')
                ->icon(Heroicon::OutlinedTrash)
                ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
        ];
    }
}
