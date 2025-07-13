<?php

namespace App\Http\Resources\Dashboard\Patient;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailsPatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'user' => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
                'phone_number' => $this->user?->phone_number,
            ],
            'date_of_birth'          => optional($this->date_of_birth)->format('Y-m-d'),
            'cpf'                    => $this->cpf,
            'gender'                 => $this->gender,
            'mother_name'            => $this->mother_name,
            'zipcode'                => $this->zipcode,
            'house_number'           => $this->house_number,
            'road'                   => $this->road,
            'neighborhood'           => $this->neighborhood,
            'complement'             => $this->complement,
            'city'                   => $this->city,
            'state'                  => $this->state,
            'profile_photo'          => $this->profile_photo ? asset($this->profile_photo) : '',
            'consulted'              => $this->consulted,
            'family_member_count'    => $this->family_member_of_patient,
            'verification_status'    => $this->verification_status,
            'verification_rejection_reason' => $this->verification_rejection_reason,

            'members' => PatientMemberResource::collection($this->whenLoaded('members')),
            'medical_records' => MedicalRecordResource::collection($this->whenLoaded('medicalRecords')),
        ];
    }
}
