<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookConsultationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'patient_id'         => ['required', 'exists:patients,id'],
            'doctor_profile_id'  => ['required', 'exists:doctor_profiles,id'],
            'coupon_code'        => ['nullable', 'string'],
            'complaint'          => ['nullable', 'string'],
            'pain_level'         => ['nullable', 'integer', 'between:0,5'],
            'consultation_date'  => ['nullable', 'date'],
            'payment_method'     => ['required', 'in:card'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
