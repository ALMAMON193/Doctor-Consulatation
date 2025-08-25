<?php

namespace App\Http\Resources\APP\Patient;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        // Generate message outside the array
        $doctor = $this->data['doctor'] ?? 'a doctor';
        $specialization = $this->data['specialization'] ?? 'your requested specialization';
        $message = "Good news! Your consultation has been accepted by Dr. {$doctor} ({$specialization}). Your doctor will contact you soon.";

        return [
            'id' => $this->id,
            'type' => $this->type,
            'consultation_id' => $this->data['consultation_id'] ?? null,
            'doctor' => $doctor,
            'specialization' => $specialization,
            'message' => $message,
            'consultation_date' => $this->data['consultation_date'] ?? null,
            'actions' => $this->data['actions'] ?? [],
            'read_at' => $this->read_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
