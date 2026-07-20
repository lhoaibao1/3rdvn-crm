<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettingsUpdated
{
    use Dispatchable, SerializesModels;

    public function broadcastOn(): array
    {
        return [new PrivateChannel('role.Admin')];
    }
}
