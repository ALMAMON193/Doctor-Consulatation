<?php

namespace App\Http\Resources\WEB\Dashboard\Doctor;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DoctorListResource extends JsonResource
{
    public function toArray($request): array
    {
        $doctorProfile = $this->doctorProfile;

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'specialty'    => $doctorProfile?->specializations?->pluck('name')->implode(', '),
            // Counts from withCount
            'consulted'    => $this->assigned_consultations_count ?? 0,
            'subscription' => $doctorProfile->subscription_status ?? 'No Sub',
            'status'       => $doctorProfile->verification_status ?? 'unverified',
            'profile_picture' => $doctorProfile && $doctorProfile->profile_picture
                ? asset(Storage::url($doctorProfile->profile_picture))
                : '',
        ];
    }
}
