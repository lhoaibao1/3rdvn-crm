<?php

namespace App\Support\Filament;

use App\Models\Lead;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\HotLeads\HotLeadConverter;
use App\Support\Permissions\HotLeadAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RecordAssignAction
{
    public static function make(string $name = 'assignProcessor'): Action
    {
        return Action::make($name)
            ->label('Gán xử lý')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->visible(fn (Model $record): bool => RecordAssignment::canAssign(auth()->user(), $record))
            ->modalHeading(fn (Model $record): string => 'Gán xử lý '.RecordAssignment::recordLabel($record))
            ->extraModalWindowAttributes(['class' => 'crm-assignment-modal'])
            ->modalWidth('md')
            ->modalAutofocus(false)
            ->modalSubmitActionLabel('Lưu phân công')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (Model $record): array => [
                TextInput::make('assignee_search')
                    ->label('Tìm nhân viên')
                    ->placeholder('Nhập tên, UID hoặc mã nhân viên')
                    ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                    ->live(debounce: 300)
                    ->dehydrated(false),
                Radio::make('assignee_id')
                    ->label('Chọn nhân viên xử lý')
                    ->options(fn (Get $get): array => [
                        0 => 'Không phân công',
                    ] + RecordAssignment::assigneeOptions(
                        $record,
                        trim((string) $get('assignee_search')) ?: null,
                    ))
                    ->default(RecordAssignment::currentAssigneeId($record) ?? 0)
                    ->columns(1)
                    ->extraAttributes(['class' => 'crm-assignee-option-list'])
                    ->required(),
            ])
            ->action(function (Model $record, array $data, mixed $livewire): void {
                $assigneeId = (int) ($data['assignee_id'] ?? 0);

                if ($assigneeId === 0) {
                    RecordAssignment::unassign($record);
                    self::refreshLivewireRecord($livewire, $record);
                    Notification::make()->title('Đã hủy phân công xử lý')->success()->send();

                    return;
                }

                $assignee = User::query()->find($assigneeId);

                if (! $assignee instanceof User) {
                    throw ValidationException::withMessages([
                        'assignee_id' => 'Vui lòng chọn nhân viên xử lý.',
                    ]);
                }

                if (! RecordAssignment::canAssignTo(auth()->user(), $record, $assignee)) {
                    throw ValidationException::withMessages([
                        'assignee_id' => 'Bạn không được phép gán hồ sơ cho nhân viên này.',
                    ]);
                }

                $promotedToLead = $record instanceof Lead && HotLeadAccess::isPendingHotLead($record);

                if ($promotedToLead) {
                    HotLeadConverter::promoteToLead($record, auth()->user(), $assignee);
                } else {
                    RecordAssignment::assign($record, $assignee);
                }

                self::refreshLivewireRecord($livewire, $record);

                Notification::make()
                    ->title($promotedToLead ? 'Đã chuyển sang Lead' : 'Đã gán xử lý')
                    ->body($promotedToLead
                        ? RecordAssignment::recordLabel($record).' đã chuyển sang Lead và gán cho '.$assignee->name.'.'
                        : RecordAssignment::recordLabel($record).' đã được gán cho '.$assignee->name.'.')
                    ->success()
                    ->send();
            });
    }

    private static function refreshLivewireRecord(mixed $livewire, Model $record): void
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

        if (! $currentRecord instanceof Model || ! $currentRecord->is($record)) {
            return;
        }

        $currentRecord->refresh();
        $livewire->record = $currentRecord;
    }
}
