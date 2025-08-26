<?php

namespace App\Events;

use App\Http\Resources\APP\Chatting\ChatMessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        // Eager load relationships to avoid N+1
        $this->message = $message->load(['sender', 'receiver', 'patientMember']);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('consultation.' . $this->message->consultation_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return (new ChatMessageResource($this->message))->toArray(null);
    }
}
