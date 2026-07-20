<?php

namespace App\Events;

use App\Models\SaleProfile;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleProfileApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public SaleProfile $profile) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.($this->profile->sale_owner_id ?: 0))];
    }
}
