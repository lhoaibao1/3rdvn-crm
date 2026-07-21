<?php

namespace App\Support\Filament;

use App\Models\Lead;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\HotLeads\HotLeadConverter;
use App\Support\Permissions\HotLeadAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
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
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Lưu phân công')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (Model $record): array => [
                Radio::make('assignee_id')
                    ->label('Chọn nhân viên xử lý')
                    ->options(fn (): array => RecordAssignment::assigneeOptions($record))
                    ->default(fn (): ?int => RecordAssignment::currentAssigneeId($record) ?? RecordAssignment::autoAssigneeForRecord($record, auth()->user())?->getKey())
                    ->columns(1)
                    ->required(),
            ])
            ->action(function (Model $record, array $data): void {
                $assignee = User::query()->find((int) ($data['assignee_id'] ?? 0));

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

                Notification::make()
                    ->title($promotedToLead ? 'Đã chuyển sang Lead' : 'Đã gán xử lý')
                    ->body($promotedToLead
                        ? RecordAssignment::recordLabel($record).' đã chuyển sang Lead và gán cho '.$assignee->name.'.'
                        : RecordAssignment::recordLabel($record).' đã được gán cho '.$assignee->name.'.')
                    ->success()
                    ->send();
            });
    }
}
