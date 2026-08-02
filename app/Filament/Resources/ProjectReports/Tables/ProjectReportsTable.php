<?php

namespace App\Filament\Resources\ProjectReports\Tables;

use App\Filament\Resources\ProjectReports\ProjectReportResource;
use App\Forms\Components\SearchableSelect as Select;
use App\Forms\Components\SearchableSelectFilter as SelectFilter;
use App\Models\ProjectReport;
use App\Models\User;
use App\Support\Filament\ProjectSchemaColumns;
use App\Support\Reports\ProjectReportAccess;
use App\Support\Reports\ProjectReportProductCatalog;
use App\Support\Reports\ProjectReportWorkflow;
use App\Support\SalesLineSnapshot;
use App\Support\VietnamAddressCatalog;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class ProjectReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'crm-users-table crm-project-reports-table'], merge: true)
            ->recordAction(null)
            ->recordUrl(fn (ProjectReport $record): string => ProjectReportResource::getUrl('view', ['record' => $record]))
            ->poll('5s')
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('salesProject.name')->label('Dự án')->badge()->color('primary')->sortable()->searchable(),
                TextColumn::make('application.application_code')->label('Mã hồ sơ')->badge()->color('info')->placeholder('-')->searchable()->toggleable(),
                TextColumn::make('origin')->label('Nguồn')->formatStateUsing(fn (?string $state): string => $state === ProjectReport::ORIGIN_APPLICATION ? 'Từ dự án' : 'Nhập báo cáo')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer_name')->label('Họ tên KH')->weight('bold')->searchable(),
                TextColumn::make('province_name')->label('Tỉnh/Thành phố')->searchable()->toggleable(),
                TextColumn::make('district_name')->label('Quận/Huyện')->searchable()->toggleable(),
                TextColumn::make('identity_number')->label('CCCD/CMND')->searchable()->toggleable(),
                TextColumn::make('phone')->label('SĐT')->searchable()->toggleable(),
                TextColumn::make('product_name')->label('Sản phẩm/Scheme')->limit(42)->tooltip(fn (ProjectReport $record): string => $record->product_name)->searchable()->toggleable(),
                TextColumn::make('loan_amount')->label('Số tiền vay')->alignEnd()->formatStateUsing(fn (int|string|null $state): string => number_format((int) $state, 0, ',', '.').' VNĐ')->sortable(),
                TextColumn::make('approved_months')->label('Kỳ hạn duyệt')->suffix(' tháng')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('approved_interest_rate')->label('Lãi suất duyệt')->suffix('%')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('sales_code')->label('Code')->badge()->color('info')->searchable(),
                TextColumn::make('status')->label('Trạng thái')->badge()->color(fn (?string $state): string => match ($state) {
                    ProjectReport::STATUS_PROCESSED => 'success',
                    ProjectReport::STATUS_REJECTED => 'danger',
                    default => 'warning',
                })->sortable(),
                ...ProjectSchemaColumns::forReports([
                    'customer_name', 'applicant_name', 'identity_number', 'cccd', 'phone',
                    'province_code', 'district_code', 'product_code', 'product_name',
                    'scheme_code', 'scheme_name', 'loan_amount', 'approved_limit',
                    'approved_amount', 'approved_months', 'approved_interest_rate',
                ]),
                TextColumn::make('createdBy.name')->label('Người tạo')->searchable()->toggleable(),
                TextColumn::make('team.name')->label('Team')->badge()->color('info')->placeholder('-')->sortable()->toggleable(),
                TextColumn::make('createdBy.uid')->label('UID người tạo')->placeholder('-')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sales_project_id')
                    ->label('Dự án')
                    ->options(fn (): array => ProjectReportAccess::projectOptions(auth()->user()))
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(ProjectReport::statusOptions())
                    ->native(false),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Xem')
                        ->url(fn (ProjectReport $record): string => ProjectReportResource::getUrl('view', ['record' => $record])),
                    EditAction::make()
                        ->label('Sửa')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin'))
                        ->url(fn (ProjectReport $record): string => ProjectReportResource::getUrl('edit', ['record' => $record])),
                    self::statusAction(),
                    DeleteAction::make()->label('Xóa')->icon(Heroicon::OutlinedTrash)->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ])
                    ->iconButton()
                    ->label('Hành động')
                    ->tooltip('Hành động')
                    ->color('gray')
                    ->size('sm')
                    ->dropdownPlacement('bottom-end')
                    ->icon(Heroicon::EllipsisVertical),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Xóa đã chọn')
                        ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
                ])
                    ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin')),
            ]);
    }

    public static function saveAsAdmin(ProjectReport $record, array $data): ProjectReport
    {
        foreach ([
            'sales_project_id', 'created_by_id', 'customer_name', 'province_code', 'district_code',
            'identity_number', 'phone', 'product_code', 'loan_amount', 'status', 'created_at', 'updated_at',
        ] as $field) {
            if (blank($data[$field] ?? null) && filled($record->{$field})) {
                $data[$field] = $record->{$field};
            }
        }

        $creator = User::query()->find($data['created_by_id'] ?? null);
        $project = ProjectReportAccess::project($data['sales_project_id'] ?? null);
        $salesCode = ProjectReportAccess::salesCode($creator, $data['sales_project_id'] ?? null);
        $productCode = trim((string) ($data['product_code'] ?? ''));
        $productName = ProjectReportProductCatalog::label($project, $productCode);
        $provinceName = VietnamAddressCatalog::provinceName($data['province_code'] ?? null);
        $districtName = VietnamAddressCatalog::districtName($data['province_code'] ?? null, $data['district_code'] ?? null);

        if (! $creator) {
            throw ValidationException::withMessages(['created_by_id' => 'Người tạo không hợp lệ.']);
        }

        if (! $project || blank($salesCode)) {
            throw ValidationException::withMessages([
                'sales_project_id' => 'Người tạo chưa được cấp dự án hoặc mã bán hàng tương ứng.',
            ]);
        }

        $status = (string) ($data['status'] ?? $record->status);
        $timestamps = array_intersect_key($data, array_flip(['created_at', 'updated_at']));
        unset($data['created_at'], $data['updated_at'], $data['status']);
        $data['sales_code'] = $salesCode;
        $data['product_name'] = $productName ?: $record->product_name;
        $data['province_name'] = $provinceName ?: $record->province_name;
        $data['district_name'] = $districtName ?: $record->district_name;
        $data['loan_amount'] = filled($data['loan_amount'] ?? null) ? (int) $data['loan_amount'] : null;
        $data = array_merge($data, SalesLineSnapshot::hierarchyFromUser($creator));

        $record->update($data);
        if ($status !== $record->status) {
            ProjectReportWorkflow::updateStatus($record->refresh(), auth()->user(), $status);
        }
        if ($timestamps !== []) {
            $usesTimestamps = $record->timestamps;
            $record->timestamps = false;
            $record->forceFill($timestamps)->save();
            $record->timestamps = $usesTimestamps;
        }

        return $record->refresh();
    }

    private static function statusAction(): Action
    {
        return Action::make('updateReportStatus')
            ->label('Cập nhật trạng thái')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('gray')
            ->visible(fn (): bool => auth()->user()?->hasRole('Admin') ?? false)
            ->modalHeading('Cập nhật trạng thái báo cáo')
            ->modalWidth('md')
            ->fillForm(fn (ProjectReport $record): array => ['status' => $record->status])
            ->schema([
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(ProjectReport::statusOptions())
                    ->required()
                    ->native(false),
            ])
            ->action(function (ProjectReport $record, array $data): void {
                ProjectReportWorkflow::updateStatus($record, auth()->user(), (string) ($data['status'] ?? ''));

                Notification::make()
                    ->title('Đã cập nhật trạng thái')
                    ->success()
                    ->send();
            });
    }
}
