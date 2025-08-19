<?php

namespace App\Http\Resources\Dashboard\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientListResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'email'                   => $this->email,
            'phone_number'            => $this->phone_number,
            'consulted'               => optional($this->patient)->consulted ?? 0,
            'member_count'            => optional($this->patient)->family_member_of_patient ?? 0,
            'verification_status'     => optional($this->patient)->verification_status ?? 'N/A',
            'profile_photo' => $this->patient && $this->patient->profile_photo
                ? asset($this->patient->profile_photo)
                : '',
        ];
    }
}
