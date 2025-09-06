<?php

namespace App\Events;

use App\Http\Resources\APP\Chatting\ChatMessageResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $consultationId;

    public function __construct($message)
    {
        $this->message = $message; // raw message or resource
        $this->consultationId = $message->consultation_id;
    }

     public function broadcastOn(): Channel
     {
          return new Channel("consultation.{$this->consultationId}");
     }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
    // ✅ Broadcast data customize
    public function broadcastWith(): array
    {
        return [
            'message' => new ChatMessageResource($this->message),
            'consultation_id' => $this->consultationId,
        ];
    }
}
