<?php

namespace App\Support\Notifications;

use Illuminate\Support\Facades\File;
use Minishlink\WebPush\VAPID;
use RuntimeException;

class VapidKeyStore
{
    private const KEY_FILE = 'app/private/web-push-vapid.json';

    public function publicKey(): string
    {
        return $this->keys()['publicKey'];
    }

    public function privateKey(): string
    {
        return $this->keys()['privateKey'];
    }

    private function keys(): array
    {
        $path = storage_path(self::KEY_FILE);
        File::ensureDirectoryExists(dirname($path), 0700, true);
        $lock = fopen($path.'.lock', 'c+');

        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('Không thể khóa khoá Web Push.');
        }

        try {
            if (File::exists($path)) {
                $keys = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
            } else {
                $keys = VAPID::createVapidKeys();
                File::put($path, json_encode($keys, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
                chmod($path, 0640);
            }

            if (blank($keys['publicKey'] ?? null) || blank($keys['privateKey'] ?? null)) {
                throw new RuntimeException('Khoá Web Push không hợp lệ.');
            }

            return $keys;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
