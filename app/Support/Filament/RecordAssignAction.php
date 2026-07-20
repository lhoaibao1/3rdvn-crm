<?php

namespace App\Support\Filament;

use App\Forms\Components\SearchableSelect as Select;
use App\Models\Lead;
use App\Models\User;
use App\Support\Assignments\RecordAssignment;
use App\Support\HotLeads\HotLeadConverter;
use App\Support\Permissions\HotLeadAccess;
use Filament\Actions\Action;
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
            ->modalWidth('md')
            ->modalSubmitActionLabel('Lưu phân công')
            ->modalCancelActionLabel('Hủy')
            ->schema(fn (Model $record): array => [
                Select::make('assignee_id')
                    ->label('Nhân viên xử lý')
                    ->options(fn (): array => RecordAssignment::assigneeOptions($record))
                    ->getSearchResultsUsing(fn (string $search): array => RecordAssignment::assigneeOptions($record, $search))
                    ->getOptionLabelUsing(function (mixed $value): ?string {
                        if (blank($value)) {
                            return null;
                        }

                        $user = User::query()->find((int) $value);

                        if (! $user instanceof User) {
                            return null;
                        }

                        return implode(' · ', array_filter([
                            $user->name,
                            $user->uid,
                            $user->employee_code,
                        ], fn (?string $label): bool => filled($label)));
                    })
                    ->default(fn (): ?int => RecordAssignment::currentAssigneeId($record) ?? RecordAssignment::autoAssigneeForRecord($record, auth()->user())?->getKey())
                    ->searchable()
                    ->searchDebounce(300)
                    ->preload()
                    ->placeholder('Tìm theo tên, UID, mã nhân viên hoặc email')
                    ->searchPrompt('Nhập ít nhất 2 ký tự để tìm nhân viên')
                    ->noSearchResultsMessage('Không tìm thấy nhân viên phù hợp.')
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
