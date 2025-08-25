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
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $timeLeft = "{$hours}h {$minutes}m left";
        } else {
            $timeLeft = 'Expired';
        }

        $patientData = $this->patient ?? $this->patientMember;

        return [
            'id' => $this->id,
            'consultation_date' => $this->consultation_date
                ? Carbon::parse($this->consultation_date)->format('d F, Y')
                : null,
            'complaint' => $this->complaint,
            'patient' => $patientData ? [
                'id' => $patientData->id,
                'name' => optional($patientData->user)->name ?? $patientData->name,
                'age' => isset($patientData->date_of_birth)
                    ? Carbon::parse($patientData->date_of_birth)->age . ' Years Old'
                    : '',
                'location' => $this->patient->city ?? '',
                'profile_photo' => optional($patientData->user)->profile_photo
                    ? asset('storage/' . $patientData->user->profile_photo)
                    : '',
            ] : [],
            'doctor_info' => [
                'specialization' => $this->specialization->name ?? '',
                'time_left' => $timeLeft,
                'amount' => 'R$' . number_format($this->final_amount, 2),
                'average_rating' => round(optional($this->doctorProfile)->ratings()->avg('rating') ?? 0, 1),
            ],
        ];
    }
}
