<?php

namespace App\Filament\Resources\FeDeeplinkApplications\Pages;

use App\Enums\FeDeeplinkStatus;
use App\Enums\FeolSubmitState;
use App\Filament\Resources\FeDeeplinkApplications\FeDeeplinkApplicationResource;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class CheckFeDeeplinkApplication extends ViewRecord
{
    protected static string $resource = FeDeeplinkApplicationResource::class;
    protected string $view = 'filament.feol.checking-application';

    public function getTitle(): string { return 'Kiểm tra điều kiện hồ sơ'; }
    public function getBreadcrumb(): string { return 'Kiểm tra điều kiện'; }

    public function refreshResult(): void
    {
        $this->record = static::getResource()::getEloquentQuery()
            ->with('feolIntegration')
            ->findOrFail($this->record->getKey());
    }

    public function outcome(): string
    {
        if ($this->record->feolIntegration?->submit_state === FeolSubmitState::FAILED) return 'submission_failed';
        $status = FeDeeplinkStatus::tryFrom((string) $this->record->feolIntegration?->sub_status);
        if ($status === FeDeeplinkStatus::ELIGIBLE && filled($this->record->feolIntegration?->deeplink_url)) return 'eligible';
        if (in_array($status, [FeDeeplinkStatus::INELIGIBLE, FeDeeplinkStatus::PRE_SCREENING_FAILURE], true)) return 'failed';
        return 'processing';
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('back')->label('Quay về danh sách')->icon(Heroicon::OutlinedArrowLeft)->url(static::getResource()::getUrl('index'))];
    }
}
