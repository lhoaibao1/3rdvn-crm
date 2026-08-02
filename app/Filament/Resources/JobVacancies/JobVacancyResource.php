<?php

namespace App\Filament\Resources\JobVacancies;

use App\Filament\Resources\JobVacancies\Pages\CreateJobVacancy;
use App\Filament\Resources\JobVacancies\Pages\EditJobVacancy;
use App\Filament\Resources\JobVacancies\Pages\ListJobVacancies;
use App\Forms\Components\SearchableSelect as Select;
use App\Forms\Components\SearchableSelectFilter as SelectFilter;
use App\Models\JobVacancy;
use App\Models\SalesProject;
use App\Support\Candidates\CandidateWorkflow;
use App\Support\UserSpecOptions;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class JobVacancyResource extends Resource
{
    protected static ?string $model = JobVacancy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    public static function getModelLabel(): string
    {
        return 'Tin tuyển dụng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tin tuyển dụng';
    }

    public static function getNavigationLabel(): string
    {
        return 'Tin tuyển dụng';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Employee - Work';
    }

    public static function getNavigationSort(): ?int
    {
        return 82;
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function canView(mixed $record): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function canEdit(mixed $record): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function canDelete(mixed $record): bool
    {
        return (bool) auth()->user()?->hasRole('Admin');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['salesProject', 'autoAssignee'])->withCount('applications');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'crm-record-form-frame'])->components([
                Section::make('Thông tin vị trí')->columns(2)->schema([
                    TextInput::make('code')->label('Mã tin')->disabled()->dehydrated(false),
                    Select::make('title')
                        ->label('Chức vụ tuyển dụng')
                        ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'name')->all())
                        ->searchable()->preload()->native(false)->required(),
                    Select::make('sales_project_id')
                        ->label('Dự án tuyển dụng')
                        ->options(fn (): array => SalesProject::query()->where('is_active', true)
                            ->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false)->required(),
                    Select::make('department')->label('Phòng ban/Bộ phận')
                        ->options(fn (): array => UserSpecOptions::departments())
                        ->searchable()->native(false),
                    TextInput::make('work_location')->label('Địa điểm làm việc')->maxLength(190),
                    Select::make('employment_type')->label('Hình thức làm việc')
                        ->options(JobVacancy::employmentTypeOptions())->default('full_time')->required()->native(false),
                    TextInput::make('quantity')->label('Số lượng tuyển')->numeric()->minValue(1)->maxValue(999)->default(1)->required(),
                    TextInput::make('experience_level')->label('Kinh nghiệm yêu cầu')->placeholder('Ví dụ: 1-2 năm')->maxLength(150),
                    DatePicker::make('application_deadline')->label('Hạn nhận hồ sơ')->displayFormat('d/m/Y')->native(false)->minDate(today()),
                    TextInput::make('salary_min')->label('Lương tối thiểu')->numeric()->minValue(0)->suffix('VNĐ'),
                    TextInput::make('salary_max')->label('Lương tối đa')->numeric()->minValue(0)->gte('salary_min')->suffix('VNĐ'),
                    Toggle::make('salary_negotiable')->label('Lương thỏa thuận')->default(true)->inline(false),
                    TextInput::make('contact_email')->label('Email tuyển dụng')->email()->maxLength(190),
                    Select::make('auto_assignee_id')
                        ->label('Người tự động nhận CV')
                        ->options(fn (): array => CandidateWorkflow::assigneeOptions())
                        ->searchable()->preload()->native(false)
                        ->helperText('Chỉ chọn được ZD, AM hoặc Team Leader đang hoạt động.'),
                    FileUpload::make('banner_path')
                        ->label('Banner tuyển dụng')
                        ->disk('public')->directory('recruitment/banners')->image()
                        ->imagePreviewHeight('180')->openable()->downloadable()
                        ->maxSize(5120)->columnSpanFull()
                        ->helperText('Ảnh ngang JPG, PNG hoặc WebP; tối đa 5 MB.'),
                ]),
                Section::make('Nội dung tuyển dụng')->schema([
                    Textarea::make('short_description')->label('Giới thiệu ngắn')->rows(2)->maxLength(500),
                    Textarea::make('description')->label('Mô tả công việc')->rows(6),
                    Textarea::make('requirements')->label('Yêu cầu ứng viên')->rows(6),
                    Textarea::make('benefits')->label('Quyền lợi')->rows(6),
                ]),
                Section::make('Hiển thị trên website')->columns(2)->schema([
                    Select::make('status')->label('Trạng thái tuyển dụng')
                        ->options(JobVacancy::statusOptions())->default(JobVacancy::STATUS_OPEN)->required()->native(false),
                    Toggle::make('is_published')->label('Hiển thị công khai')->helperText('Tắt để ẩn tin khỏi trang ứng tuyển.')->default(false)->inline(false),
                    Toggle::make('is_featured')->label('Đánh dấu nổi bật')->default(false)->inline(false),
                    TextInput::make('sort_order')->label('Thứ tự hiển thị')->numeric()->minValue(0)->default(0),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                TextColumn::make('code')->label('Mã tin')->badge()->color('info')->searchable()->sortable(),
                TextColumn::make('title')->label('Vị trí tuyển dụng')->weight('bold')->searchable()->sortable()->wrap(),
                TextColumn::make('salesProject.name')->label('Dự án')->badge()->color('success')->searchable()->sortable(),
                TextColumn::make('department')->label('Phòng ban')->searchable()->toggleable(),
                TextColumn::make('work_location')->label('Địa điểm')->searchable()->toggleable(),
                SelectColumn::make('status')->label('Tình trạng')->options(JobVacancy::statusOptions())->sortable(),
                ToggleColumn::make('is_published')->label('Hiển thị')->sortable(),
                TextColumn::make('applications_count')->label('CV đã nhận')->numeric()->sortable(),
                TextColumn::make('autoAssignee.name')->label('Người nhận CV')->placeholder('Chưa phân công')->searchable()->toggleable(),
                TextColumn::make('application_deadline')->label('Hạn nhận CV')->date('d/m/Y')->placeholder('Không giới hạn')->sortable(),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sales_project_id')->label('Dự án')
                    ->options(fn (): array => SalesProject::query()->where('is_active', true)
                        ->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                    ->native(false),
                SelectFilter::make('status')->label('Tình trạng')->options(JobVacancy::statusOptions())->native(false),
                TernaryFilter::make('is_published')->label('Hiển thị trên website')
                    ->trueLabel('Đang hiển thị')->falseLabel('Đang ẩn')->placeholder('Tất cả'),
            ])
            ->recordUrl(fn (JobVacancy $record): string => static::getUrl('edit', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->label('Chỉnh sửa tin'),
                    DeleteAction::make()->label('Xóa')->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ])->iconButton()->label('Hành động')->icon(Heroicon::EllipsisVertical),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobVacancies::route('/'),
            'create' => CreateJobVacancy::route('/create'),
            'edit' => EditJobVacancy::route('/{record}/edit'),
        ];
    }
}
