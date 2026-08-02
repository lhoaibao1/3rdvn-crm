<?php

namespace App\Filament\Resources\CandidateApplications;

use App\Filament\Resources\CandidateApplications\Pages\EditCandidateApplication;
use App\Filament\Resources\CandidateApplications\Pages\ListCandidateApplications;
use App\Filament\Resources\CandidateApplications\Pages\ViewCandidateApplication;
use App\Forms\Components\SearchableSelect as Select;
use App\Forms\Components\SearchableSelectFilter as SelectFilter;
use App\Models\CandidateApplication;
use App\Support\Candidates\CandidateWorkflow;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CandidateApplicationResource extends Resource
{
    protected static ?string $model = CandidateApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function getModelLabel(): string
    {
        return 'CV - Ứng viên';
    }

    public static function getPluralModelLabel(): string
    {
        return 'CV - Ứng viên';
    }

    public static function getNavigationLabel(): string
    {
        return 'CV - Ứng viên';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Employee - Work';
    }

    public static function getNavigationSort(): ?int
    {
        return 81;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return CandidateWorkflow::canAccess(auth()->user());
    }

    public static function canViewAny(): bool
    {
        return CandidateWorkflow::canAccess(auth()->user());
    }

    public static function canView(mixed $record): bool
    {
        return $record instanceof CandidateApplication
            && CandidateWorkflow::canView($record, auth()->user());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof CandidateApplication
            && CandidateWorkflow::canEdit($record, auth()->user());
    }

    public static function canDelete(mixed $record): bool
    {
        return $record instanceof CandidateApplication
            && (auth()->user()?->hasRole('Admin') ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'reviewedBy',
            'convertedUser',
            'assignedTo.roles',
            'assignedBy',
            'approvedBy',
        ]);

        return CandidateWorkflow::scopeVisible($query, auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])->components([
                Section::make('Tiếp nhận & xử lý')->columns(3)->schema([
                    TextInput::make('application_code')->label('Mã ứng tuyển')->disabled()->dehydrated(false),
                    Select::make('status')
                        ->label('Trạng thái')
                        ->options(CandidateApplication::statusOptions())
                        ->native(false)
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('source')->label('Nguồn')->disabled()->dehydrated(false),
                    Textarea::make('internal_note')->label('Ghi chú nội bộ')->rows(3)->columnSpanFull(),
                ]),
                Section::make('Thông tin ứng viên')->columns(2)->schema([
                    TextInput::make('full_name')->label('Họ và tên')->required()->maxLength(150),
                    TextInput::make('applied_position')->label('Vị trí ứng tuyển')->required()->maxLength(150),
                    TextInput::make('email')->label('Email')->email()->required()->maxLength(190),
                    TextInput::make('phone')->label('Số điện thoại')->tel()->required()->maxLength(24),
                    DatePicker::make('date_of_birth')->label('Ngày sinh')->displayFormat('d/m/Y')->native(false),
                    Select::make('gender')->label('Giới tính')->options([
                        'male' => 'Nam',
                        'female' => 'Nữ',
                        'other' => 'Khác',
                    ])->native(false),
                    TextInput::make('current_position')->label('Vị trí hiện tại/gần nhất'),
                    TextInput::make('latest_company')->label('Công ty gần nhất'),
                    TextInput::make('experience_years')->label('Số năm kinh nghiệm')->numeric()->minValue(0)->maxValue(60),
                    TextInput::make('education_level')->label('Trình độ học vấn'),
                    TextInput::make('expected_salary')->label('Mức lương mong muốn')->numeric()->suffix('VNĐ'),
                    DatePicker::make('available_from')->label('Có thể bắt đầu từ ngày')->displayFormat('d/m/Y')->native(false),
                    Textarea::make('cover_letter')->label('Giới thiệu ngắn')->rows(4)->columnSpanFull(),
                ]),
                Section::make('Địa chỉ')->columns(2)->schema([
                    TextInput::make('province_name')->label('Tỉnh/Thành phố'),
                    TextInput::make('district_name')->label('Quận/Huyện'),
                    TextInput::make('ward_name')->label('Phường/Xã'),
                    TextInput::make('address_line')->label('Địa chỉ chi tiết'),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Ứng viên')->columnSpanFull()->tabs([
                Tab::make('Hồ sơ ứng tuyển')->icon(Heroicon::Identification)->columns(12)->schema([
                    Section::make('Thông tin chính')->columnSpan(8)->columns(2)->schema([
                        TextEntry::make('application_code')->label('Mã ứng tuyển')->badge()->color('info'),
                        TextEntry::make('applied_position')->label('Vị trí ứng tuyển'),
                        TextEntry::make('full_name')->label('Họ và tên'),
                        TextEntry::make('email')->label('Email'),
                        TextEntry::make('phone')->label('Số điện thoại'),
                        TextEntry::make('date_of_birth')->label('Ngày sinh')->date('d/m/Y')->placeholder('-'),
                        TextEntry::make('gender')->label('Giới tính')->formatStateUsing(
                            fn (?string $state): string => ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$state] ?? '-'
                        ),
                        TextEntry::make('full_address')
                            ->label('Địa chỉ')
                            ->state(fn (CandidateApplication $record): string => collect([
                                $record->address_line,
                                $record->ward_name,
                                $record->district_name,
                                $record->province_name,
                            ])->filter()->join(', '))
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
                    Section::make('Trạng thái')->columnSpan(4)->schema([
                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => CandidateApplication::statusLabel($state))
                            ->color(fn (?string $state): string => CandidateApplication::statusColor($state)),
                        TextEntry::make('assignedTo.name')->label('Người phỏng vấn')->placeholder('Chưa phân công'),
                        TextEntry::make('created_at')->label('Nộp lúc')->dateTime('H:i d/m/Y'),
                        TextEntry::make('convertedUser.uid')->label('UID đã cấp')->badge()->color('success')->placeholder('-'),
                        TextEntry::make('convertedUser.employee_code')->label('Employee Code')->placeholder('-'),
                    ]),
                ]),
                Tab::make('Phỏng vấn & phê duyệt')->icon(Heroicon::ClipboardDocumentCheck)->columns(2)->schema([
                    Section::make('Phân công')->columns(2)->schema([
                        TextEntry::make('assignedTo.name')->label('Người được giao')->placeholder('-'),
                        TextEntry::make('assignedBy.name')->label('Người phân công')->placeholder('-'),
                        TextEntry::make('assigned_at')->label('Thời gian phân công')->dateTime('H:i d/m/Y')->placeholder('-'),
                        TextEntry::make('interview_at')->label('Thời gian phỏng vấn')->dateTime('H:i d/m/Y')->placeholder('-'),
                    ])->columnSpanFull(),
                    Section::make('Kết quả phỏng vấn')->schema([
                        TextEntry::make('interview_recommendation')
                            ->label('Đề xuất')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => CandidateApplication::recommendationLabel($state))
                            ->color(fn (?string $state): string => $state === 'hire' ? 'success' : ($state === 'reject' ? 'danger' : 'gray')),
                        TextEntry::make('interview_note')->label('Nhận xét phỏng vấn')->placeholder('-'),
                        TextEntry::make('submitted_at')->label('Trình duyệt lúc')->dateTime('H:i d/m/Y')->placeholder('-'),
                    ]),
                    Section::make('Phê duyệt tuyển dụng')->schema([
                        TextEntry::make('approvedBy.name')->label('Người phê duyệt')->placeholder('-'),
                        TextEntry::make('approved_at')->label('Phê duyệt lúc')->dateTime('H:i d/m/Y')->placeholder('-'),
                        TextEntry::make('approval_note')->label('Ghi chú phê duyệt')->placeholder('-'),
                    ]),
                ]),
                Tab::make('Kinh nghiệm')->icon(Heroicon::Briefcase)->columns(2)->schema([
                    TextEntry::make('current_position')->label('Vị trí hiện tại/gần nhất')->placeholder('-'),
                    TextEntry::make('latest_company')->label('Công ty gần nhất')->placeholder('-'),
                    TextEntry::make('experience_years')->label('Số năm kinh nghiệm')->suffix(' năm')->placeholder('-'),
                    TextEntry::make('education_level')->label('Trình độ học vấn')->placeholder('-'),
                    TextEntry::make('expected_salary')->label('Mức lương mong muốn')->money('VND')->placeholder('-'),
                    TextEntry::make('available_from')->label('Có thể bắt đầu')->date('d/m/Y')->placeholder('-'),
                    TextEntry::make('cover_letter')->label('Giới thiệu ngắn')->columnSpanFull()->placeholder('-'),
                ]),
                Tab::make('CV & xử lý')->icon(Heroicon::DocumentArrowDown)->columns(2)->schema([
                    TextEntry::make('cv_download')
                        ->label('CV đính kèm')
                        ->state('Tải CV ứng viên')
                        ->url(fn (CandidateApplication $record): string => route('recruitment.cv.download', $record))
                        ->openUrlInNewTab()
                        ->color('primary'),
                    TextEntry::make('source')->label('Nguồn')->placeholder('-'),
                    TextEntry::make('internal_note')->label('Ghi chú nội bộ')->columnSpanFull()->placeholder('-'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('application_code')->label('Mã CV')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('full_name')->label('Ứng viên')->weight('bold')->searchable()->sortable(),
                TextColumn::make('applied_position')->label('Vị trí ứng tuyển')->searchable()->sortable(),
                TextColumn::make('assignedTo.name')->label('Người phỏng vấn')->placeholder('Chưa phân công')->searchable(),
                TextColumn::make('phone')->label('Số điện thoại')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CandidateApplication::statusLabel($state))
                    ->color(fn (?string $state): string => CandidateApplication::statusColor($state))
                    ->sortable(),
                TextColumn::make('convertedUser.uid')->label('UID')->badge()->color('success')->placeholder('-')->toggleable(),
                TextColumn::make('created_at')->label('Ngày nộp')->dateTime('H:i d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(CandidateApplication::statusOptions())->native(false),
                SelectFilter::make('assigned_to_id')
                    ->label('Người phỏng vấn')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordUrl(fn (CandidateApplication $record): string => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Xem hồ sơ'),
                    EditAction::make()
                        ->label('Cập nhật thông tin')
                        ->visible(fn (CandidateApplication $record): bool => CandidateWorkflow::canEdit($record, auth()->user())),
                    DeleteAction::make()->label('Xóa')->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ])->iconButton()->label('Hành động')->icon(Heroicon::EllipsisVertical),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidateApplications::route('/'),
            'view' => ViewCandidateApplication::route('/{record}'),
            'edit' => EditCandidateApplication::route('/{record}/edit'),
        ];
    }
}
