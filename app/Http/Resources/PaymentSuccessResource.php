<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $patient
 * @property mixed $patientMember
 * @property mixed $doctorProfile
 * @property mixed $patient_member_id
 * @property mixed $patient_id
 */
class PaymentSuccessResource extends JsonResource
{
    public function toArray($request): array
    {
        $doctor = $this->doctorProfile;

        // Determine who paid
        $paidBy = null;

        if ($this->patient_id && $this->patient) {
            $patient = $this->patient;
            $paidBy = [
                'id'                => $patient->id,
                'name'              => $patient->name ?? ($patient->user->name ?? ''),
                'type'              => 'patient',
                'profile_picture'   => $this->normalizeProfilePicture('storage/' .$patient->profile_photo ?? ''),
            ];
        } elseif ($this->patient_member_id && $this->patientMember) {
            $member = $this->patientMember;
            $paidBy = [
                'id'                => $member->id,
                'name'              => $member->name ?? '',
                'type'              => 'patient_member',
                'profile_picture'   => $this->normalizeProfilePicture('storage/' .$member->profile_photo ?? ''),
            ];
        }

        return [
            'doctorProfile' => [
                'id'                 => $doctor->id,
                'name'               => $doctor->name ?? ($doctor->user->name ?? ''),
                'profile_picture'    => $this->normalizeProfilePicture('storage/' .$doctor->profile_picture ?? ''),
                'type'               => 'doctor_profile',
                'consultation_time'  => $doctor->consultation_time,
                'paid_by'            => $paidBy,
            ],
        ];
    }

    /**
     * Normalize profile picture URL.
     */
    protected function normalizeProfilePicture($value): string
    {
        if ($value === null || $value === '' || $value === 'null') {
            return '';
        }
        return asset($value);
    }
}
