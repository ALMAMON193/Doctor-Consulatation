<?php

namespace App\Http\Resources\Dashboard\Doctor;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $doctorProfile = $this->doctorProfile;

        return [
            'id'        =>$this->id,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone_number,
            'specialty'    => $doctorProfile?->specializations?->pluck('name')->implode(', '),
            'consulted_consultation' => $this->assigned_consultations_count ?? 0,
            'completed_consultation' => $this->completed_consultations_count ?? 0,
            'cancel_consultation' => $this->cancel_consultation ?? 0,
            'ratting' =>  1020,
            // Fix here by using toArray()
            'doctor_profile'   => optional($this->doctorProfile)?->toArray(),
            'address'          => optional($this->address)?->toArray(),
            'personal_details' => optional($this->personalDetails)?->toArray(),
        ];
    }
}
