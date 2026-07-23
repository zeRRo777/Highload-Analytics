<?php

namespace App\Events;

use App\Http\Resources\ImportResource;
use App\Models\Import;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Import $import
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('imports.' . $this->import->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ImportUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'data' => (new ImportResource($this->import))->resolve()
        ];
    }
}
