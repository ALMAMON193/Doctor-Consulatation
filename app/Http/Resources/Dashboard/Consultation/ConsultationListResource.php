<?php

namespace App\Http\Resources\Dashboard\Consultation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationListResource extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'id'     =>$this->id,
            'patient' => $this->patientMember->name
                ?? optional($this->patient->user)->name
                    ?? null,

            'patient_photo' => $this->patientMember->photo
                ?? optional($this->patient)->photo
                    ?? '',

            'doctor' => optional($this->doctorProfile->user)->name ?? '',
            'doctor_photo' => optional($this->doctorProfile)->photo ?? '',
            'specialty' => optional($this->doctorProfile)->specialization ?? '',
            'consultation_date' => $this->created_at->format('Y-m-d'),
            'payment_status'    => $this->payment_status,
            'consultation_status'=>$this->consultation_status,
            'time' => $this->created_at->format('h:iA'),
        ];
    }
}
