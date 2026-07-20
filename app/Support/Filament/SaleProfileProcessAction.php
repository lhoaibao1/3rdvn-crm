<?php

namespace App\Support\Filament;

use App\Models\SaleProfile;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class SaleProfileProcessAction
{
    public static function make(?callable $recordResolver = null): Action
    {
        return Action::make('processSaleProfile')
            ->label('Xử lý Hồ sơ')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (?SaleProfile $record = null): bool => self::canProcess(self::resolveRecord($recordResolver, $record)))
            ->modalHeading(fn (?SaleProfile $record = null): string => 'Xử lý Hồ sơ #'.(self::resolveRecord($recordResolver, $record)?->getKey() ?: ''))
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-process-modal'])
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Lưu xử lý')
            ->modalCancelActionLabel('Hủy')
            ->fillForm(fn (?SaleProfile $record = null): array => self::initialData(self::resolveRecord($recordResolver, $record)))
            ->form(fn (?SaleProfile $record = null): array => self::form(self::resolveRecord($recordResolver, $record)))
            ->action(function (array $data, ?SaleProfile $record = null) use ($recordResolver): void {
                $profile = self::resolveRecord($recordResolver, $record);

                if (! $profile instanceof SaleProfile || ! auth()->user()) {
                    return;
                }

                $status = $data['processing_status'];
                $profile->forceFill([
                    'processing_status' => $status,
                    'status' => match ($status) {
                        'completed' => 'completed',
                        'rejected' => 'rejected',
                        'processing' => 'processing',
                        default => $profile->status ?: 'new',
                    },
                    'approval_status' => match ($status) {
                        'completed' => 'approved',
                        'rejected' => 'rejected',
                        default => $profile->approval_status ?: 'pending',
                    },
                    'note' => $data['processing_note'] ?? $profile->note,
                    'rejection_reason' => $status === 'rejected' ? ($data['processing_note'] ?? $profile->rejection_reason) : $profile->rejection_reason,
                    'approved_by_id' => in_array($status, ['completed', 'rejected'], true) ? auth()->id() : $profile->approved_by_id,
                    'approved_at' => in_array($status, ['completed', 'rejected'], true) ? now() : $profile->approved_at,
                    'completed_at' => $status === 'completed' ? now() : $profile->completed_at,
                ])->save();

                Notification::make()
                    ->title('Đã lưu xử lý Hồ sơ')
                    ->success()
                    ->send();
            });
    }

    private static function resolveRecord(?callable $recordResolver, ?SaleProfile $record): ?SaleProfile
    {
        return $recordResolver ? $recordResolver($record) : $record;
    }

    private static function canProcess(?SaleProfile $profile): bool
    {
        $user = auth()->user();

        return $profile instanceof SaleProfile
            && $user instanceof User
            && ! $profile->trashed()
            && ($user->hasRole('Admin') || (int) $profile->processing_owner_id === (int) $user->getKey());
    }

    private static function initialData(?SaleProfile $profile): array
    {
        return [
            'processing_status' => $profile?->processing_status ?: 'pending',
            'processing_note' => $profile?->note,
        ];
    }

    private static function form(?SaleProfile $profile): array
    {
        return [
            Placeholder::make('processor_display')
                ->label('Người được phân xử lý')
                ->content($profile?->processingOwner?->name ?: 'Admin'),
            Placeholder::make('sale_owner_display')
                ->label('Nhân viên bán hàng')
                ->content($profile?->saleOwner?->name ?: '-'),
            Select::make('processing_status')
                ->label('Quyết định xử lý')
                ->options([
                    'pending' => 'Chờ xử lý',
                    'processing' => 'Đang xử lý',
                    'completed' => 'Hoàn tất',
                    'rejected' => 'Từ chối',
                ])
                ->required()
                ->native(false),
            Textarea::make('processing_note')
                ->label('Ghi chú xử lý')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }
}
