<?php

namespace App\Filament\Resources\ProjectReports\Pages;

use App\Filament\Resources\ProjectReports\ProjectReportResource;
use App\Filament\Resources\ProjectReports\Tables\ProjectReportsTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;

class EditProjectReport extends EditRecord
{
    protected static string $resource = ProjectReportResource::class;

    public function getTitle(): string
    {
        return 'Sửa báo cáo';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return ProjectReportsTable::saveAsAdmin($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

}
