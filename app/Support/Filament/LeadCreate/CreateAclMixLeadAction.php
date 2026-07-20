<?php

namespace App\Support\Filament\LeadCreate;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class CreateAclMixLeadAction
{
    use CreatesLeadRecords;

    public static function make(): Action
    {
        return Action::make('createAclMixLead')
            ->label('ACL Mix')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->visible(fn (): bool => self::canCreateForProject('acl-mix'))
            ->modalHeading('Tạo Lead ACL Mix')
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-create-modal'])
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('Gửi Lead Kiểm Tra')
            ->modalSubmitAction(fn (Action $action): Action => $action->icon(Heroicon::OutlinedPaperAirplane))
            ->modalCancelActionLabel('Hủy')
            ->schema(self::schema())
            ->action(fn (array $data, mixed $livewire): mixed => self::createLeadForProject($data, 'acl-mix', self::fieldKeys(), $livewire, true));
    }

    private static function schema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Họ tên khách hàng')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->required()
                        ->maxLength(30),
                    TextInput::make('identity_number')
                        ->label('CCCD/CMND')
                        ->required()
                        ->maxLength(30),
                    TextInput::make('birthday')
                        ->label('Ngày sinh')
                        ->mask('99/99/9999')
                        ->placeholder('dd/mm/yyyy')
                        ->required()
                        ->rule('date_format:d/m/Y')
                        ->maxLength(10),
                    Select::make('noi_cap')
                        ->label('Nơi cấp')
                        ->options([
                            'CCS' => 'CCS',
                            'Bộ Công An' => 'Bộ Công An',
                        ])
                        ->placeholder('Chọn nơi cấp')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    TextInput::make('date_cap')
                        ->label('Ngày cấp')
                        ->mask('99/99/9999')
                        ->placeholder('dd/mm/yyyy')
                        ->required()
                        ->rule('date_format:d/m/Y')
                        ->maxLength(10),
                    ...LeadAddressFields::make(),
                ]),
        ];
    }

    private static function fieldKeys(): array
    {
        return [
            'customer_name', 'phone', 'identity_number', 'birthday', 'noi_cap', 'date_cap', 'address',
            'province_code', 'province_name', 'district_code', 'district_name', 'ward_code', 'ward_name',
        ];
    }
}
