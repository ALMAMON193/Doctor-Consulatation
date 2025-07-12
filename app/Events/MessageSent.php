<?php

namespace App\Events;

use App\Http\Resources\ChatMessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): Channel
    {
        if ($this->message->receiver_patient_member_id) {
            return new PrivateChannel("chat.doctor.{$this->message->sender_doctor_profile_id}.member.{$this->message->receiver_patient_member_id}");
        }

        return new PrivateChannel("chat.doctor.{$this->message->sender_doctor_profile_id}.patient.{$this->message->receiver_patient_id}");
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return (new ChatMessageResource($this->message))->toArray(request());
    }
}
