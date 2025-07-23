<?php

namespace App\Http\Resources\Dashboard\Doctor;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $doctorProfile
 */
class DoctorListResource extends JsonResource
{
    public function toArray($request): array
    {
        $doctorProfile = $this->doctorProfile;

        $completedCount = 0;

        if ($doctorProfile) {
            $completedCount = $doctorProfile->completedConsultations()
                ->where('consultation_status', 'completed')
                ->count();
        }
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'specialty'    => optional($doctorProfile)->specialization,
            'consulted'    => $completedCount,
            'subscription' => optional($doctorProfile)->subscription ?? 'No Sub',
            'status'       => optional($doctorProfile)->verification_status,
        ];
    }


}
