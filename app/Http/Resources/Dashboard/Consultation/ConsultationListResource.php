<?php

namespace App\Http\Resources\Dashboard\Consultation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationListResource extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'patient' => $this->patientName ?? "N/A",
            'patient_photo' => optional($this->patientMember)->profile_photo
                ? asset('storage/' . $this->patientMember->profile_photo)
                : '',
            'doctor' => $this->doctorName ?? "N/A",
            'profile_picture' => optional($this->doctorProfile)->profile_picture
                ? asset('storage/' . $this->doctorProfile->profile_picture)
                : '',
            'specialty' => $this->specializationName,
            'consultation_date' => $this->consultation_date->format('Y-m-d'),
            'payment_status' => $this->payment_status,
            'consultation_status' => $this->consultation_status === 'completed' ? 'Ended' : $this->consultation_status,
            'time' => $this->created_at->format('h:iA'),
        ];
    }
}
