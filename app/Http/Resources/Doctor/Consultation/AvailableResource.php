<?php

namespace App\Http\Resources\Doctor\Consultation;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $consultation_date
 * @property mixed $final_amount
 * @property mixed $id
 * @property mixed $complaint
 * @property mixed $patient
 * @property mixed $patientMember
 * @property mixed $specialization
 * @property mixed $doctorProfile
 */
class AvailableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now();
        $consultationDate = Carbon::parse($this->consultation_date);

        if ($consultationDate->isFuture()) {
            $totalMinutes = $now->diffInMinutes($consultationDate);
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $timeLeft = "{$hours} hour" . ($hours !== 1 ? 's' : '') . " {$minutes} minute" . ($minutes !== 1 ? 's' : '') . " left";
        } else {
            $timeLeft = 'Expired';
        }

        return [
            'id' => $this->id,
            'consultation_date' => $this->consultation_date
                ? Carbon::parse($this->consultation_date)->format('d,F,Y')
                : null,
            'timeLeft' => $timeLeft,
            'amount' => intval($this->final_amount),
            'complaint' => $this->complaint,
            'specialization' => $this->specialization->name,
            'patient' => $this->patient ? [
                'id' => $this->patient->id,
                'name' => optional($this->patient->user)->name,
                'age' => $this->patient->date_of_birth
                    ? Carbon::parse($this->patient->date_of_birth)->age . ' Years Old'
                    : '',
                'profile_photo' => optional($this->patient->user)->profile_photo
                    ? asset('storage/' . $this->patient->user->profile_photo)
                    : '',
            ] : [],
            'patient_member' => $this->patientMember ? [
                'id' => $this->patientMember->id,
                'name' => $this->patientMember->name,
                'profile_photo' => optional($this->patientMember->user)->profile_photo
                    ? asset('storage/' . $this->patientMember->user->profile_photo)
                    : '',
            ] : [],
            'doctor_info' => [
                'doctor_name' => optional(optional($this->doctorProfile)->user)->name,
                'average_rating' => round(optional($this->doctorProfile)->ratings()->avg('rating') ?? 0, 1),
            ],
        ];
    }
}
