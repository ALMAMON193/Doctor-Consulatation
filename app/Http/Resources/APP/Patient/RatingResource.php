<?php

namespace App\Http\Resources\APP\Patient;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $doctor_profile_id
 * @property mixed $doctorProfile
 * @property mixed $patient_id
 * @property mixed $id
 * @property mixed $patient
 * @property mixed $patient_member_id
 * @property mixed $patientMember
 * @property mixed $rating
 * @property mixed $comment
 * @property mixed $created_at
 */
class RatingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'patient_id'        => $this->patient_id,
            'patient_member_id' => $this->patient_member_id,
            'doctor_profile_id' => $this->doctor_profile_id,
            'rating'            => $this->rating,
            'review'            => $this->review,
            'created_at'        => $this->created_at->toDateTimeString(),
            'updated_at'        => $this->updated_at->toDateTimeString(),
        ];
    }
}
