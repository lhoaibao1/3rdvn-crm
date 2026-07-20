<?php

namespace App\Filament\Resources\SaleProfiles\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use App\Forms\Components\SearchableSelect as Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SaleProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name')->label('Khách hàng')->required(),
                TextInput::make('phone')->label('Số điện thoại')->tel(),
                TextInput::make('email')->label('Thư điện tử')->email(),
                TextInput::make('identity_number')->label('CCCD/CMND'),
                TextInput::make('product_interest')->label('Sản phẩm'),
                Textarea::make('address')->label('Địa chỉ')->rows(2)->columnSpanFull(),
                Select::make('sale_owner_id')->label('Nhân viên bán hàng')->relationship('saleOwner', 'name')->searchable()->preload()->native(false),
                Select::make('processing_owner_id')->label('Người xử lý')->relationship('processingOwner', 'name')->searchable()->preload()->native(false),
                Select::make('status')->label('Trạng thái')->options([
                    'new' => 'Mới',
                    'processing' => 'Đang xử lý',
                    'completed' => 'Hoàn tất',
                    'rejected' => 'Từ chối',
                ])->required()->native(false)->default('new'),
                Select::make('approval_status')->label('Trạng thái phê duyệt')->options([
                    'pending' => 'Chờ duyệt',
                    'approved' => 'Đã duyệt',
                    'rejected' => 'Từ chối',
                ])->required()->native(false)->default('pending'),
                Select::make('processing_status')->label('Trạng thái xử lý')->options([
                    'pending' => 'Chờ xử lý',
                    'processing' => 'Đang xử lý',
                    'completed' => 'Hoàn tất',
                    'rejected' => 'Từ chối',
                ])->native(false),
                Textarea::make('note')->label('Ghi chú')->rows(3)->columnSpanFull(),
                Textarea::make('rejection_reason')->label('Lý do từ chối')->rows(3)->columnSpanFull(),
                TextInput::make('source_lead_id')->label('Lead nguồn')->numeric(),
                Select::make('approved_by_id')->label('Người duyệt')->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->native(false),
                DateTimePicker::make('approved_at')->label('Thời điểm duyệt')->displayFormat('H:i d/m/Y'),
                DateTimePicker::make('completed_at')->label('Hoàn tất lúc')->displayFormat('H:i d/m/Y'),
            ]);
    }
}
