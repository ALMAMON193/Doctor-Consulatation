<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatParticipantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'doctorProfile' => collect($this['doctorProfile'])->map(function ($doctor) {
                return [
                    'id'              => $doctor->id,
                    'name'            => $doctor->personal_name,
                    'profile_picture' => $this->normalizeProfilePicture($doctor->profile_picture ?? null),
                    'type'            => 'doctor_profile',
                    'paid_by'         => $doctor->paid_by ?? [],
                ];
            }),

            'patientProfile' => [
                'id'              => $this['patientProfile']->id,
                'name'            => $this['patientProfile']->user->name ?? null,
                'profile_picture' => $this->normalizeProfilePicture($this['patientProfile']->profile_photo ?? null),
                'type'            => 'patient',
                'members'         => $this['patientProfile']->patientMembers->map(function ($member) {
                    return [
                        'id'            => $member->id,
                        'name'          => $member->name,
                        'profile_photo' => $this->normalizeProfilePicture($member->profile_photo ?? null),
                        'type'          => 'patient_member',
                    ];
                }),
            ],
        ];
    }

    protected function normalizeProfilePicture($value): string
    {
        if ($value === 'null' || $value === null || $value === '') {
            return '';
        }
        return $value;
    }
}

