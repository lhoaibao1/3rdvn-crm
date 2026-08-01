<?php

namespace App\Filament\Resources\ProjectReports\Pages;

use App\Filament\Resources\ProjectReports\ProjectReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProjectReport extends ViewRecord
{
    protected static string $resource = ProjectReportResource::class;

    public function getTitle(): string
    {
        return 'Báo cáo';
    }

    public function getBreadcrumb(): string
    {
        return 'Xem';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Xóa báo cáo')
                ->icon(Heroicon::OutlinedTrash)
                ->visible(fn (): bool => (bool) auth()->user()?->hasRole('Admin'))
                ->successRedirectUrl(ProjectReportResource::getUrl('index')),
        ];
    }
}
