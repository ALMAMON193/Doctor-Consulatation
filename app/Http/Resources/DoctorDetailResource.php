<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone_number,
            'specialty' => optional($this->doctorProfile)->specialization,
            'consulted' => optional($this->doctorProfile)->consulted ?? 0,
            'subscription' => optional($this->doctorProfile)->subscription ?? "No Sub",
            'status'    => optional($this->doctorProfile)->verification_status,

            // Fix here by using toArray()
            'doctor_profile'   => optional($this->doctorProfile)?->toArray(),
            'address'          => optional($this->address)?->toArray(),
            'personal_details' => optional($this->personalDetails)?->toArray(),
        ];
    }
}
