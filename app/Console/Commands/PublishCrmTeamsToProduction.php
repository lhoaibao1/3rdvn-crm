<?php

namespace App\Console\Commands;

use App\Models\CrmTeam;
use App\Services\CrmTeamProductionPublisher;
use Illuminate\Console\Command;
use Throwable;

class PublishCrmTeamsToProduction extends Command
{
    protected $signature = 'crm:publish-teams
        {--team= : ID hoặc mã Team cần đồng bộ}
        {--force : Bỏ qua câu hỏi xác nhận}';

    protected $description = 'Phát hành cấu hình Team từ UAT sang Prod';

    public function handle(CrmTeamProductionPublisher $publisher): int
    {
        if (! config('crm.team_publication.enabled')) {
            $this->error('Chức năng phát hành Team chưa được bật.');

            return self::FAILURE;
        }

        $query = CrmTeam::query()
            ->with(['manager', 'members'])
            ->orderBy('id');

        if (filled($team = $this->option('team'))) {
            $query->where(function ($query) use ($team): void {
                $query
                    ->whereKey(is_numeric($team) ? (int) $team : 0)
                    ->orWhere('code', $team);
            });
        }

        $teams = $query->get();

        if ($teams->isEmpty()) {
            $this->warn('Không tìm thấy Team để đồng bộ.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Đồng bộ {$teams->count()} Team từ UAT sang Prod?",
        )) {
            return self::SUCCESS;
        }

        foreach ($teams as $team) {
            try {
                $publisher->publish($team);
                $this->info("Đã đồng bộ {$team->code} · {$team->name}");
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Không thể đồng bộ {$team->code}: {$exception->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
