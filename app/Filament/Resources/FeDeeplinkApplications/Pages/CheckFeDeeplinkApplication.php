<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Pages;

use App\Enums\FeDeeplinkStatus;
use App\Filament\Resources\FeDeeplinkApplications\FeDeeplinkApplicationResource;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class CheckFeDeeplinkApplication extends ViewRecord
{
    protected static string $resource = FeDeeplinkApplicationResource::class;

    protected string $view = 'filament.feol.checking-application';

    public function getTitle(): string
    {
        return 'Kiểm tra điều kiện hồ sơ';
    }

    public function getBreadcrumb(): string
    {
        return 'Kiểm tra điều kiện';
    }

    public function isEligible(): bool
    {
        return $this->record->feolIntegration?->sub_status === FeDeeplinkStatus::ELIGIBLE;
    }

    public function isIneligible(): bool
    {
        return in_array($this->record->feolIntegration?->sub_status, [
            FeDeeplinkStatus::INELIGIBLE,
            FeDeeplinkStatus::PRE_SCREENING_FAILURE,
        ], true);
    }

    public function deeplink(): ?string
    {
        return $this->isEligible() ? $this->record->feolIntegration?->deeplink_url : null;
    }

    public function refreshResult(): void
    {
        /** @var Application $application */
        $application = static::getResource()::getEloquentQuery()
            ->with('feolIntegration')
            ->findOrFail($this->record->getKey());

        $this->record = $application;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Quay về danh sách')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
