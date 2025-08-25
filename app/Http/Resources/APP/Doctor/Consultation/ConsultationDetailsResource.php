<?php

namespace App\Http\Resources\APP\Doctor\Consultation;

use Carbon\Carbon;
use Carbon\CarbonInterface;
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
class ConsultationDetailsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $now = Carbon::now();
        $consultationDate = Carbon::parse($this->consultation_date);

        $timeLeft = $consultationDate->isFuture()
            ? $now->diffForHumans($consultationDate, ['parts' => 2, 'short' => true, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]) . ' left'
            : 'Expired';

        return [
            'id' => $this->id,
            'consultation_date' => $this->consultation_date
                ? Carbon::parse($this->consultation_date)->format('d,F,Y')
                : null,
            'timeLeft' => $timeLeft,
            'amount' => intval($this->final_amount),
            'complaint' => $this->complaint,
            'specialization' => $this->specialization->name ?? '',

            'patient' => $this->patient ? [
                'id' => $this->patient->id,
                'name' => optional($this->patient->user)->name,
                'age' => $this->patient->date_of_birth
                    ? Carbon::parse($this->patient->date_of_birth)->age . ' Years Old'
                    : '',
                'profile_photo' => optional($this->patient->user)->profile_photo
                    ? asset('storage/' . $this->patient->user->profile_photo)
                    : '',
                'medical_records' => $this->patient->medicalRecords->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'type' => $record->record_type,
                        'date' => $record->record_date,
                        'file_url' => $record->file_path ? asset('storage/' . $record->file_path) : '',
                    ];
                }),
            ] : '',

            'patient_member' => $this->patientMember ? [
                'id' => $this->patientMember->id,
                'name' => $this->patientMember->name,
                'profile_photo' => optional($this->patientMember->user)->profile_photo
                    ? asset('storage/' . $this->patientMember->user->profile_photo)
                    : '',
                'medical_records' => $this->patientMember->medicalRecords->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'type' => $record->record_type,
                        'date' => $record->record_date,
                        'file_url' => $record->file_path ? asset('storage/' . $record->file_path) : '',
                    ];
                }),
            ] : '',

            'doctor_info' => [
                'doctor_name' => optional(optional($this->doctorProfile)->user)->name,
                'average_rating' => round(optional($this->doctorProfile)->ratings()->avg('rating') ?? 0, 1),
            ],
        ];
    }
}
