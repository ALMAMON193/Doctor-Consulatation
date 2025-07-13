<?php

namespace App\Http\Resources\Dashboard\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'name' => $this->name,
//            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'relationship' => $this->relationship,
            'cpf' => $this->cpf,
            'gender' => $this->gender,
            'profile_photo' => $this->profile_photo ? asset($this->profile_photo) : null,
//            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
