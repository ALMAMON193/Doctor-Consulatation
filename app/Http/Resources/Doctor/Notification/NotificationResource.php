<?php

namespace App\Http\Resources\Doctor\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'consultation_id' => $this->data['consultation_id'] ?? null,
            'specialization' => $this->data['specialization'] ?? null,
            'message' => $this->data['message']
                ?? 'A new paid consultation is available in your specialization: '
                . ($this->data['specialization'] ?? 'Unknown')
                . '. You can apply to accept this consultation.',
            'consultation_date' => $this->data['consultation_date'] ?? null,
            'actions' => $this->data['actions'] ?? [],
            'read_at' => $this->read_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
