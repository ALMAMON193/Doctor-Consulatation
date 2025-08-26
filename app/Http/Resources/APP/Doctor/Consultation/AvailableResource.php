<?php

namespace App\Http\Resources\APP\Doctor\Consultation;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now();
        $consultationDate = $this->consultation_date ? Carbon::parse($this->consultation_date) : null;

        if ($consultationDate && $consultationDate->isFuture()) {
            $totalMinutes = $now->diffInMinutes($consultationDate);
            $hours        = floor($totalMinutes / 60);
            $minutes      = $totalMinutes % 60;
            $timeLeft     = "{$hours}h {$minutes}m left";
        } else {
            $timeLeft = 'Expired';
        }

        /**
         * Patient Info
         * Priority:
         *  1. If consultation has patient_member → show patient_member details
         *     but id = parent patient id
         *  2. Else → show real patient details
         */
        if ($this->patientMember) {
            $parentPatient = $this->patientMember->patient;

            $patientData = [
                'id'            => $parentPatient->id ?? null, // parent patient id
                'name'          => $this->patientMember->name,
                'age'           => $this->patientMember->date_of_birth
                    ? Carbon::parse($this->patientMember->date_of_birth)->age . ' Years Old'
                    : '',
                'profile_photo' => $this->patientMember->profile_photo
                    ? asset('storage/' . $this->patientMember->profile_photo)
                    : '',
            ];
        } elseif ($this->patient) {
            $patientData = [
                'id'            => $this->patient->id,
                'name'          => optional($this->patient->user)->name ?? $this->patient->name,
                'age'           => isset($this->patient->date_of_birth)
                    ? Carbon::parse($this->patient->date_of_birth)->age . ' Years Old'
                    : '',
                'profile_photo' => $this->patient->profile_photo
                    ? asset('storage/' . $this->patient->profile_photo)
                    : (optional($this->patient->user)->profile_photo
                        ? asset('storage/' . $this->patient->user->profile_photo)
                        : ''),
            ];
        } else {
            $patientData = null;
        }

        return [
            'id' => $this->id,
            'consultation_date' => $this->consultation_date
                ? Carbon::parse($this->consultation_date)->format('d F, Y')
                : null,
            'complaint' => $this->complaint,

            'patient' => $patientData,

            'doctor_info' => [
                'id'              => optional($this->doctorProfile)->id,
                'specialization'  => $this->specialization->name ?? '',
                'time_left'       => $timeLeft,
                'amount'          => 'R$' . number_format($this->final_amount, 2),
                'average_rating'  => round(optional($this->doctorProfile)->ratings()->avg('rating') ?? 0, 1),
            ],
        ];
    }
}
