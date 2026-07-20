<?php

namespace App\Filament\Resources\SaleProfiles\Schemas;

use App\Models\SaleProfile;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SaleProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    public static function components(): array
    {
        return [
            Tabs::make('Sale profile detail')
                ->columnSpanFull()
                ->persistTabInQueryString('profile_tab')
                ->tabs([
                    Tab::make('Hồ sơ')
                        ->icon(Heroicon::ClipboardDocumentList)
                        ->columns(12)
                        ->schema([
                            Section::make('Thông tin khách hàng')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('id')->label('Mã Hồ sơ')->formatStateUsing(fn (mixed $state): string => 'HS #'.$state)->badge()->color('info'),
                                    TextEntry::make('status')->label('Trạng thái')->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->badge()->placeholder('-'),
                                    TextEntry::make('customer_name')->label('Khách hàng')->placeholder('-'),
                                    TextEntry::make('phone')->label('Số điện thoại')->placeholder('-'),
                                    TextEntry::make('email')->label('Thư điện tử')->placeholder('-'),
                                    TextEntry::make('identity_number')->label('CCCD/CMND')->placeholder('-'),
                                    TextEntry::make('product_interest')->label('Sản phẩm')->placeholder('-'),
                                    TextEntry::make('address')->label('Địa chỉ')->placeholder('-')->columnSpanFull(),
                                    TextEntry::make('note')->label('Ghi chú')->placeholder('-')->columnSpanFull(),
                                ]),
                            Section::make('Nguồn Hồ sơ')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('sourceLead.lead_code')->label('Mã Lead')->placeholder('-')->badge()->color('gray'),
                                    TextEntry::make('saleOwner.name')->label('Nhân viên bán hàng')->placeholder('-'),
                                    TextEntry::make('processingOwner.name')->label('Người xử lý')->placeholder('-'),
                                    TextEntry::make('team.name')->label('Nhóm')->placeholder('-'),
                                ]),
                        ]),
                    Tab::make('Xử lý')
                        ->icon(Heroicon::ClipboardDocumentCheck)
                        ->columns(12)
                        ->schema([
                            Section::make('Trạng thái xử lý')
                                ->columnSpan(8)
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('processing_status')->label('Trạng thái xử lý')->formatStateUsing(fn (?string $state): string => self::processingLabel($state))->badge()->placeholder('-'),
                                    TextEntry::make('approval_status')->label('Trạng thái phê duyệt')->formatStateUsing(fn (?string $state): string => self::approvalLabel($state))->badge()->placeholder('-'),
                                    TextEntry::make('rejection_reason')->label('Lý do từ chối')->placeholder('-')->columnSpanFull(),
                                ]),
                            Section::make('Hệ thống')
                                ->columnSpan(4)
                                ->schema([
                                    TextEntry::make('approvedBy.name')->label('Người duyệt')->placeholder('-'),
                                    TextEntry::make('approved_at')->label('Thời điểm duyệt')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('completed_at')->label('Hoàn tất lúc')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('created_at')->label('Ngày tạo')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('updated_at')->label('Cập nhật')->dateTime('H:i d/m/Y')->placeholder('-'),
                                    TextEntry::make('deleted_at')->label('Đã xóa')->dateTime('H:i d/m/Y')->visible(fn (SaleProfile $record): bool => $record->trashed()),
                                ]),
                        ]),
                ]),
        ];
    }

    private static function statusLabel(?string $state): string
    {
        return match ($state) {
            'new' => 'Mới',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn tất',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }

    private static function processingLabel(?string $state): string
    {
        return match ($state) {
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn tất',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }

    private static function approvalLabel(?string $state): string
    {
        return match ($state) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => $state ?: '-',
        };
    }
}
