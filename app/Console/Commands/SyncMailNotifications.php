<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StalwartMailService;
use App\Support\Notifications\MailNotificationSender;
use Illuminate\Console\Command;
use Throwable;

class SyncMailNotifications extends Command
{
    protected $signature = 'crm:mail-notifications';

    protected $description = 'Đồng bộ sự kiện mail vào thông báo CRM';

    public function handle(StalwartMailService $mail): int
    {
        $created = 0;

        User::query()
            ->whereNotNull('mail_account_id')
            ->whereNotNull('mail_address')
            ->where('mail_status', User::MAIL_STATUS_ACTIVE)
            ->chunkById(50, function ($users) use ($mail, &$created): void {
                foreach ($users as $user) {
                    try {
                        $created += MailNotificationSender::synchronize($user, $mail);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            });

        $this->info("Đã tạo {$created} thông báo mail.");

        return self::SUCCESS;
    }
}
