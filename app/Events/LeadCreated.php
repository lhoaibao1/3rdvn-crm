<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.($this->lead->assigned_sale_id ?: 0))];
    }
}
