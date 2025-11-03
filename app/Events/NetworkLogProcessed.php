<?php

namespace App\Events;

use App\Models\NetworkLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NetworkLogProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public NetworkLog $networkLog;
    public array $results;

    /**
     * Create a new event instance.
     */
    public function __construct(NetworkLog $networkLog, array $results = [])
    {
        $this->networkLog = $networkLog;
        $this->results = $results;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('network-logs'),
            new PrivateChannel('dashboard'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'network_log' => [
                'id' => $this->networkLog->id,
                'file_name' => $this->networkLog->file_name,
                'status' => $this->networkLog->status,
                'upload_date' => $this->networkLog->upload_date->toISOString(),
            ],
            'results' => $this->results,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'network-log.processed';
    }
}
