<?php

namespace App\Http\Resources\Dashboard\Consultation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationDetailResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consultation_date' => $this->consultation_date,
            'complaint' => $this->complaint,
            'pain_level' => $this->pain_level,
            'fee_amount' => $this->fee_amount,
            'discount_amount' => $this->discount_amount,
            'final_amount' => $this->final_amount,
            'coupon_code' => $this->coupon_code,
            'payment_status' => $this->payment_status,
            'consultation_status' => $this->consultation_status,

            'patient_information' => $this->patient ? [
                'id' => $this->patient->id,
                'name' => $this->patient->user->name ?? null,
                'phone' => $this->patient->user->phone_number ?? null,
                'email' => $this->patient->user->email ?? null,
            ] : [],

            'patient_member_information' => $this->patientMember ? [
                'id' => $this->patientMember->id,
                'name' => $this->patientMember->name,
            ] : [],

            'doctor_information' => $this->doctorProfile ? [
                'id' => $this->doctorProfile->id,
                'name' => $this->doctorProfile->user->name ?? null,
            ] : [],
        ];
    }

}
