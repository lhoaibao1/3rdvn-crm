<?php

namespace App\Filament\Resources\ProjectReports\Schemas;

use App\Models\ProjectReport;
use App\Support\Filament\RecordViewChrome;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ProjectReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            TextEntry::make('project_report_record_view_header')
                ->hiddenLabel()
                ->state(fn (ProjectReport $record): HtmlString => RecordViewChrome::projectReport($record))
                ->html()
                ->columnSpanFull(),
            Tabs::make('Chi tiết báo cáo')
                ->columnSpanFull()
                ->persistTabInQueryString('project_report_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::RectangleStack)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin báo cáo')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('customer_name')->label('Họ tên khách hàng')->placeholder('-'),
                                    TextEntry::make('identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('phone')->label('Số điện thoại')->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->badge()->color('primary')->placeholder('-'),
                                    TextEntry::make('province_name')->label('Tỉnh/Thành phố')->placeholder('-'),
                                    TextEntry::make('district_name')->label('Quận/Huyện')->placeholder('-'),
                                    TextEntry::make('product_name')->label('Sản phẩm/Scheme')->placeholder('-'),
                                    TextEntry::make('loan_amount')
                                        ->label('Số tiền vay')
                                        ->formatStateUsing(fn (int|string|null $state): string => number_format((int) $state, 0, ',', '.').' VNĐ')
                                        ->placeholder('-'),
                                    TextEntry::make('sales_code')->label('Mã bán hàng')->badge()->color('info')->placeholder('-'),
                                ]),
                            Section::make('Hệ thống')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('status')
                                        ->label('Trạng thái')
                                        ->badge()
                                        ->color(fn (?string $state): string => match ($state) {
                                            ProjectReport::STATUS_PROCESSED => 'success',
                                            ProjectReport::STATUS_REJECTED => 'danger',
                                            default => 'warning',
                                        }),
                                    TextEntry::make('origin')
                                        ->label('Nguồn')
                                        ->formatStateUsing(fn (?string $state): string => $state === ProjectReport::ORIGIN_APPLICATION ? 'Từ dự án' : 'Nhập báo cáo'),
                                    TextEntry::make('createdBy.name')->label('Người tạo')->placeholder('-'),
                                    TextEntry::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                ]),
                        ]),
                    Tab::make('Liên kết dự án')
                        ->icon(Heroicon::Briefcase)
                        ->columns(12)
                        ->schema([
                            Section::make('Hồ sơ liên kết')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('application.application_code')->label('Mã hồ sơ')->badge()->color('info')->placeholder('-'),
                                    TextEntry::make('salesProject.name')->label('Dự án')->badge()->color('primary')->placeholder('-'),
                                    TextEntry::make('convertedBy.name')->label('Người thực hiện chuyển')->placeholder('-'),
                                    TextEntry::make('converted_at')->label('Thời gian chuyển')->dateTime('H:i d/m/Y')->placeholder('-'),
                                ]),
                            Section::make('Cập nhật trạng thái')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('statusUpdatedBy.name')->label('Người cập nhật')->placeholder('-'),
                                    TextEntry::make('status_updated_at')->label('Thời gian cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                ]),
                        ]),
                ]),
        ];
    }
}
