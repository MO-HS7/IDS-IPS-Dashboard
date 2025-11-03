<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveNetworkDataEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $sessionId;
    public $packetData;
    public $statistics;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $sessionId, array $packetData, array $statistics = [])
    {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->packetData = $packetData;
        $this->statistics = $statistics;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('network.live.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'packet.captured';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'packet' => $this->packetData,
            'statistics' => $this->statistics,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
