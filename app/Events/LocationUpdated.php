<?php


namespace App\Events;

use App\Models\RideRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ride;

    public function __construct(RideRequest $ride)
    {
        $this->ride = $ride;
    }

    public function broadcastOn()
    {
        return new Channel('ride.' . $this->ride->id);
    }
}
