<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\{Consultation, DoctorProfile};

class PatientHomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $patient = $this->patient;
        $patient_photo = $patient->profile_photo ? asset($patient->profile_photo) : '';
        $fields = [
            'date_of_birth' => 'Please enter your date of birth.',
            'cpf' => 'Please provide your CPF number.',
            'gender' => 'Please select your gender.',
            'mother_name' => 'Please add your mother’s name.',
            'zipcode' => 'Please provide your ZIP code.',
            'house_number' => 'Please enter your house number.',
            'road' => 'Please provide your road/street name.',
            'neighborhood' => 'Please enter your neighborhood.',
            'complement' => 'Please fill in the address complement.',
            'city' => 'Please provide your city.',
            'state' => 'Please select your state.',
            'profile_photo' => 'Please upload your profile photo.',
        ];
        $nextStep = [];
        $complete = [];
        foreach ($fields as $key => $msg) {
            if (empty($patient->$key)) {
                $nextStep[] = ['message' => $msg];
            } else {
                $complete[] = ['message' => ucfirst(str_replace('_', ' ', $key)) . ' has been completed.'];
            }
        }
        $total = count($fields);
        $done = count($complete);
        $percent = $total ? round(($done / $total) * 100) : 0;
        return [
            'active_doctors'        => $this->getActiveDoctorsCount(),
            'specialist_online'     => $this->getOnlineSpecialistCount(),
            'your_consultation'     => $this->getUserConsultationCount(),
            'complete_registration' => [
                'percentage'     => $percent,
                'patient_photo'  => $patient_photo,
                'next_step'      => $nextStep,
                'complete'       => $complete,
            ],
            'all_specialists' => [
                'specialization' => $this->getAllSpecializations(),
            ],
        ];
    }
    protected function getActiveDoctorsCount(): int
    {
        return DoctorProfile::where('verification_status', 'approved')->count();
    }
    protected function getOnlineSpecialistCount(): int
    {
        return DoctorProfile::where('is_active', true)->count();
    }
    protected function getUserConsultationCount(): int
    {
        $user = auth('sanctum')->user();

        if ($user->patient) {
            $memberIds = $user->patient->patientMembers()->pluck('id');
            return Consultation::whereIn('payment_status', ['paid', 'completed'])
                ->where(function ($q) use ($user, $memberIds) {
                    $q->where('patient_id', $user->patient->id)
                        ->orWhereIn('patient_member_id', $memberIds);
                })->count();
        }
        if ($user->patientMember) {
            return Consultation::where('patient_member_id', $user->patientMember->id)
                ->whereIn('payment_status', ['paid', 'completed'])->count();
        }
        return 0;
    }
    protected function getAllSpecializations(): array
    {
        return DoctorProfile::whereNotNull('specialization')
            ->pluck('specialization')
            ->flatMap(function ($specialization) {
                // decode json if stored as json string
                $values = is_string($specialization) ? json_decode($specialization, true) : $specialization;
                return (array) $values;
            })
            ->unique()
            ->values()
            ->toArray();
    }

}
