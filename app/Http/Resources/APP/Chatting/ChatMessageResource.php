<?php

namespace App\Http\Resources\APP\Chatting;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'consultation_id' => $this->consultation_id,

            'sender' => [
                'id' => $this->sender?->id,
                'name' => $this->sender?->name ?? 'Unknown',
            ],

            'receiver' => [
                'id' => $this->receiver?->id,
                'name' => $this->receiver?->name ?? 'Unknown',
            ],

            'patient' => [
                'id' => $this->patient?->id,
                'name' => $this->patient?->user?->name ?? 'Unknown',
            ],

            'patient_member' => $this->patientMember
                ? [
                    'id' => $this->patientMember->id,
                    'name' => $this->patientMember->name,
                ]
                : null,

            'content' => $this->content,
            'file' => $this->file ? asset('storage/' . $this->file) : null,
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
