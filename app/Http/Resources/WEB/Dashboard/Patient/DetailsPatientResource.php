<?php

namespace App\Http\Resources\WEB\Dashboard\Patient;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailsPatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [
            'consultation_statistics' => [
                'all_consultation'      => $this->consultations->count(),
                'completed'             => $this->consultations->where('consultation_status', 'completed')->count(),
                'canceled'              => $this->consultations->where('consultation_status', 'cancelled')->count(),
                'home_consultation'     =>$this->consultations->where('consultation_status', 'home')->count(),
                'chat_consultation'     =>$this->consultations->where('consultation_status', 'chat')->count(),
            ],
            'family_member' => $this->members->map(function ($member) {
                return [
                    'name' => $member->name,
                    'relationship' => $member->relationship,
                    'profile_photo' => $member->profile_photo ? asset($member->profile_photo) : '',
                ];
            }),
            'account' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
            ],
            'personal_info' => [
                'name'          =>optional($this->user)->name,
                'cpf'           => $this->cpf,
                'gender'        => ucfirst($this->gender),
                'mother_name'   => $this->mother_name,
                'date_of_birth' => $this->date_of_birth
                    ? Carbon::parse($this->date_of_birth)->format('jS F, Y') . ' | ' . Carbon::parse($this->date_of_birth)->age . ' years old'
                    : null,
                'profile_photo' =>$this->profile_photo ? asset($this->profile_photo) : '',
            ],
            'address' => [
                'zipcode' => $this->zipcode,
                'house_number' => $this->house_number,
                'road' => $this->road,
                'neighborhood' => $this->neighborhood,
                'complement' => $this->complement,
                'city' => $this->city,
                'state' => $this->state,
            ],
        ];
    }
}
