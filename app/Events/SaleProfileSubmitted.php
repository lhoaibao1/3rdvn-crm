<?php

namespace App\Events;

use App\Models\SaleProfile;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleProfileSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public SaleProfile $profile) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('role.Manager'), new PrivateChannel('role.Admin')];
    }
}
