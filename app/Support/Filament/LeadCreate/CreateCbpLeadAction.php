<?php

namespace App\Support\Filament\LeadCreate;

use App\Support\AdminWorkflowOverride;
use App\Support\CustomerName;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class CreateCbpLeadAction
{
    use CreatesLeadRecords;

    public static function make(): Action
    {
        return Action::make('createCbpLead')
            ->label('CBP')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->visible(fn (): bool => self::canCreateForProject('cbp'))
            ->modalHeading('Tạo Lead CBP')
            ->extraModalWindowAttributes(['class' => 'crm-lead-modal crm-lead-create-modal'])
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Gửi Lead Kiểm Tra')
            ->modalSubmitAction(fn (Action $action): Action => $action->icon(Heroicon::OutlinedPaperAirplane))
            ->modalCancelActionLabel('Hủy')
            ->schema(self::schema())
            ->action(fn (array $data, mixed $livewire): mixed => self::createLeadForProject($data, 'cbp', self::fieldKeys(), $livewire));
    }

    private static function schema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Họ tên')
                        ->required(AdminWorkflowOverride::required())
                        ->maxLength(255)
                        ->extraInputAttributes(['class' => 'crm-uppercase-input'])
                        ->dehydrateStateUsing(fn (?string $state): ?string => CustomerName::normalize($state)),
                    TextInput::make('identity_number')
                        ->label('CCCD')
                        ->required(AdminWorkflowOverride::required())
                        ->maxLength(30),
                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->required(AdminWorkflowOverride::required())
                        ->maxLength(30),
                ]),
        ];
    }

    private static function fieldKeys(): array
    {
        return ['customer_name', 'identity_number', 'phone'];
    }
}
