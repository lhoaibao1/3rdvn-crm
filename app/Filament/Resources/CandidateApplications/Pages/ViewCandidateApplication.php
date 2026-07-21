<?php

namespace App\Filament\Resources\CandidateApplications\Pages;

use App\Filament\Resources\CandidateApplications\CandidateApplicationResource;
use App\Filament\Resources\Users\UserResource;
use App\Forms\Components\SearchableSelect as Select;
use App\Models\CandidateApplication;
use App\Support\Candidates\CandidateConversionService;
use App\Support\Candidates\CandidateWorkflow;
use App\Support\RoleHierarchy;
use App\Support\UserSpecOptions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Password;

class ViewCandidateApplication extends ViewRecord
{
    protected static string $resource = CandidateApplicationResource::class;

    public function getTitle(): string
    {
        return 'Hồ sơ ứng viên';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadCv')
                    ->label('Tải CV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (): string => route('recruitment.cv.download', $this->getRecord()))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->label('Cập nhật thông tin')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (): bool => CandidateWorkflow::canEdit($this->getRecord(), auth()->user())),
                Action::make('assignInterview')
                    ->label('Phân công phỏng vấn')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->color('info')
                    ->modalHeading('Phân công người quản lý phỏng vấn')
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Lưu phân công')
                    ->modalCancelActionLabel('Hủy')
                    ->visible(fn (): bool => CandidateWorkflow::canAssign($this->getRecord(), auth()->user()))
                    ->schema([
                        Radio::make('assigned_to_id')
                            ->label('Chọn người quản lý phỏng vấn')
                            ->options(fn (): array => [
                                0 => 'None - Không phân công',
                            ] + CandidateWorkflow::assigneeOptions(auth()->user()))
                            ->default(fn (): int => (int) ($this->getRecord()->assigned_to_id ?? 0))
                            ->columns(1)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $assigneeId = (int) ($data['assigned_to_id'] ?? 0);

                        if ($assigneeId === 0) {
                            CandidateWorkflow::unassign($this->getRecord(), auth()->user());
                        } else {
                            CandidateWorkflow::assign(
                                $this->getRecord(),
                                $assigneeId,
                                auth()->user(),
                            );
                        }

                        $this->getRecord()->refresh();
                        Notification::make()
                            ->title($assigneeId === 0 ? 'Đã hủy phân công phỏng vấn' : 'Đã phân công phỏng vấn')
                            ->success()
                            ->send();
                    }),
                Action::make('startInterview')
                    ->label('Bắt đầu phỏng vấn')
                    ->icon(Heroicon::OutlinedVideoCamera)
                    ->visible(fn (): bool => CandidateWorkflow::canInterview($this->getRecord(), auth()->user())
                        && $this->getRecord()->status === CandidateApplication::STATUS_ASSIGNED)
                    ->requiresConfirmation()
                    ->action(function (): void {
                        CandidateWorkflow::startInterview($this->getRecord(), auth()->user());
                        $this->getRecord()->refresh();
                        Notification::make()->title('Đã bắt đầu phỏng vấn')->success()->send();
                    }),
                Action::make('submitInterview')
                    ->label('Trình kết quả phỏng vấn')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->visible(fn (): bool => CandidateWorkflow::canInterview($this->getRecord(), auth()->user()))
                    ->schema([
                        DateTimePicker::make('interview_at')
                            ->label('Thời gian phỏng vấn')
                            ->default(now())
                            ->displayFormat('H:i d/m/Y')
                            ->seconds(false)
                            ->native(false)
                            ->required(),
                        Select::make('interview_recommendation')
                            ->label('Đề xuất')
                            ->options(CandidateApplication::recommendationOptions())
                            ->native(false)
                            ->required(),
                        Textarea::make('interview_note')
                            ->label('Nhận xét phỏng vấn')
                            ->rows(5)
                            ->required()
                            ->maxLength(5000),
                    ])
                    ->action(function (array $data): void {
                        CandidateWorkflow::submitInterview($this->getRecord(), $data, auth()->user());
                        $this->getRecord()->refresh();
                        Notification::make()->title('Đã trình hồ sơ chờ phê duyệt')->success()->send();
                    }),
                Action::make('recruitmentDecision')
                    ->label('Phê duyệt tuyển dụng')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn (): bool => CandidateWorkflow::canApprove($this->getRecord(), auth()->user()))
                    ->schema([
                        Select::make('decision')
                            ->label('Quyết định')
                            ->options([
                                'approved' => 'Phê duyệt tuyển dụng',
                                'rejected' => 'Không phê duyệt tuyển dụng',
                            ])
                            ->native(false)
                            ->required(),
                        Textarea::make('approval_note')
                            ->label('Ghi chú phê duyệt')
                            ->rows(4)
                            ->maxLength(5000),
                    ])
                    ->action(function (array $data): void {
                        CandidateWorkflow::decide(
                            $this->getRecord(),
                            $data['decision'] === 'approved',
                            $data['approval_note'] ?? null,
                            auth()->user(),
                        );
                        $this->getRecord()->refresh();
                        Notification::make()->title('Đã cập nhật quyết định tuyển dụng')->success()->send();
                    }),
                Action::make('convert')
                    ->label('Cấp mã nhân sự')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->color('success')
                    ->visible(fn (): bool => CandidateWorkflow::canIssueCode($this->getRecord(), auth()->user()))
                    ->modalHeading('Cấp tài khoản cho nhân sự')
                    ->modalDescription('Hệ thống tự cấp UID và Employee Code sau khi tạo tài khoản.')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email đăng nhập')
                            ->email()
                            ->required()
                            ->default(fn (): string => $this->getRecord()->email),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->default(fn (): string => $this->getRecord()->phone),
                        TextInput::make('password')
                            ->label('Mật khẩu tạm thời')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::min(8)->mixedCase()->numbers()),
                        Select::make('role')
                            ->label('Vai trò')
                            ->options(fn (): array => RoleHierarchy::assignableRoleOptions())
                            ->required()
                            ->native(false),
                        TextInput::make('position')
                            ->label('Chức danh')
                            ->default(fn (): string => $this->getRecord()->applied_position),
                        Select::make('department')
                            ->label('Phòng ban')
                            ->options(fn (): array => UserSpecOptions::departments())
                            ->searchable()
                            ->native(false),
                        Select::make('office')
                            ->label('Office')
                            ->options(fn (): array => UserSpecOptions::offices())
                            ->searchable()
                            ->native(false),
                        Select::make('contract_type')
                            ->label('Loại hợp đồng')
                            ->options(fn (): array => UserSpecOptions::contractTypes())
                            ->native(false),
                        DatePicker::make('hire_date')
                            ->label('Ngày vào làm')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Select::make('zd_id')
                            ->label('ZD quản lý')
                            ->options(fn (): array => UserSpecOptions::roleUsers('ZD'))
                            ->searchable()
                            ->native(false),
                        Select::make('am_id')
                            ->label('AM quản lý')
                            ->options(fn (): array => UserSpecOptions::roleUsers('AM'))
                            ->searchable()
                            ->native(false),
                        Select::make('team_leader_id')
                            ->label('Team Leader quản lý')
                            ->options(fn (): array => UserSpecOptions::roleUsers('Team Leader'))
                            ->searchable()
                            ->native(false),
                    ])
                    ->action(function (array $data): void {
                        $user = app(CandidateConversionService::class)->convert(
                            $this->getRecord(),
                            $data,
                            auth()->user(),
                        );

                        Notification::make()
                            ->title('Đã cấp tài khoản nhân sự')
                            ->body($user->uid.' / '.$user->employee_code)
                            ->success()
                            ->send();

                        $this->redirect(UserResource::getUrl('view', ['record' => $user]));
                    }),
                Action::make('back')
                    ->label('Quay lại')
                    ->color('gray')
                    ->icon(Heroicon::OutlinedArrowLeft)
                    ->url(CandidateApplicationResource::getUrl('index')),
            ])->button()->label('Hành động')->icon(Heroicon::EllipsisHorizontal),
        ];
    }
}
