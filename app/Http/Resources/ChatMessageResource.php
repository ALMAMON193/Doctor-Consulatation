<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'consultation_id' => $this->consultation_id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name ?? 'Unknown',
            ],
            'patient' => [
                'id' => $this->patient->id,
                'name' => $this->patient->user->name ?? 'Unknown',
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name ?? 'Unknown',
            ],
            'content' => $this->content,
            'file' => $this->file ? asset('storage/' . $this->file) : null, // Serve file from storage
            'is_read' => $this->is_read,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
