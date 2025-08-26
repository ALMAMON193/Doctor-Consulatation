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
        $consultationDate = $this->consultation_date ? Carbon::parse($this->consultation_date) : null;

        $timeLeft = ($consultationDate && $consultationDate->isFuture())
            ? $now->diffForHumans($consultationDate, [
                'parts' => 2,
                'short' => true,
                'syntax' => CarbonInterface::DIFF_ABSOLUTE
            ]) . ' left'
            : 'Expired';

        /**
         * Patient logic:
         * - If patient_member exists → use member details, but parent patient id
         * - Else → use patient details
         */
        if ($this->patientMember) {
            $parentPatient = $this->patientMember->patient;

            $patientData = [
                'id'   => $parentPatient->id ?? null, // ✅ parent patient id
                'name' => $this->patientMember->name,
                'age'  => $this->patientMember->date_of_birth
                    ? Carbon::parse($this->patientMember->date_of_birth)->age . ' Years Old'
                    : '',
                'profile_photo' => $this->patientMember->profile_photo
                    ? asset('storage/' . $this->patientMember->profile_photo)
                    : '',
                'medical_records' => $this->patientMember->medicalRecords
                    ? $this->patientMember->medicalRecords->map(function ($record) {
                        return [
                            'id'       => $record->id,
                            'type'     => $record->record_type,
                            'date'     => $record->record_date,
                            'file_url' => $record->file_path ? asset('storage/' . $record->file_path) : '',
                        ];
                    })
                    : [],
            ];
        } elseif ($this->patient) {
            $patientData = [
                'id'   => $this->patient->id,
                'name' => optional($this->patient->user)->name ?? $this->patient->name,
                'age'  => $this->patient->date_of_birth
                    ? Carbon::parse($this->patient->date_of_birth)->age . ' Years Old'
                    : '',
                'profile_photo' => $this->patient->profile_photo
                    ? asset('storage/' . $this->patient->profile_photo)
                    : (optional($this->patient->user)->profile_photo
                        ? asset('storage/' . $this->patient->user->profile_photo)
                        : ''),
                'medical_records' => $this->patient->medicalRecords
                    ? $this->patient->medicalRecords->map(function ($record) {
                        return [
                            'id'       => $record->id,
                            'type'     => $record->record_type,
                            'date'     => $record->record_date,
                            'file_url' => $record->file_path ? asset('storage/' . $record->file_path) : '',
                        ];
                    })
                    : [],
            ];
        } else {
            $patientData = null;
        }

        return [
            'id'                => $this->id,
            'consultation_date' => $consultationDate
                ? $consultationDate->format('d F, Y')
                : null,
            'timeLeft'     => $timeLeft,
            'amount'       => intval($this->final_amount),
            'complaint'    => $this->complaint,
            'specialization' => $this->specialization->name ?? '',
            'patient'      => $patientData,
            'doctor_info'  => [
                'id'             => optional($this->doctorProfile)->id,
                'doctor_name'    => optional(optional($this->doctorProfile)->user)->name,
                'average_rating' => round(optional($this->doctorProfile)->ratings()->avg('rating') ?? 0, 1),
            ],
        ];
    }
}
