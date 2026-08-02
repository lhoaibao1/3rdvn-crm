<?php

namespace App\Filament\Resources\CrmTeams\Pages\Concerns;

use App\Models\CrmTeam;
use App\Services\CrmTeamProductionPublisher;
use Filament\Notifications\Notification;
use Throwable;

trait PublishesCrmTeamToProduction
{
    protected function publishTeamToProduction(): void
    {
        $team = $this->getRecord();

        if (! $team instanceof CrmTeam) {
            return;
        }

        try {
            if (! app(CrmTeamProductionPublisher::class)->publish($team)) {
                return;
            }

            Notification::make()
                ->success()
                ->title('Đã đồng bộ Team lên Prod')
                ->body('Thông tin Team và thành viên trên Prod đã được cập nhật.')
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('Team đã lưu ở UAT nhưng chưa đồng bộ được Prod')
                ->body('Vui lòng thử lưu lại hoặc báo Admin kiểm tra kết nối.')
                ->persistent()
                ->send();
        }
    }
}
