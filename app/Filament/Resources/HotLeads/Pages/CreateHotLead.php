<?php

namespace App\Filament\Resources\HotLeads\Pages;

use App\Filament\Resources\HotLeads\HotLeadResource;
use App\Filament\Resources\HotLeads\Tables\HotLeadsTable;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateHotLead extends CreateRecord
{
    protected static string $resource = HotLeadResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Tạo Lead nóng';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(HotLeadsTable::createFormComponents());
    }

    protected function handleRecordCreation(array $data): Model
    {
        return HotLeadsTable::createHotLead($data);
    }

    protected function getRedirectUrl(): string
    {
        return HotLeadResource::getUrl('index');
    }
}
