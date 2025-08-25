<?php

namespace App\Http\Requests\APP\Patient;

use Illuminate\Foundation\Http\FormRequest;

class BookConsultationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => 'nullable|exists:patients,id',
            'patient_member_id' => 'nullable|exists:patient_members,id',
            'specialization_id' => 'required|exists:specializations,id',
            'coupon_code' => 'nullable|string',
            'complaint' => 'nullable|string|max:2000',
            'pain_level' => 'nullable|integer|between:0,10',
            'consultation_date' => 'nullable|date|after_or_equal:today',
            'consultation_type' => 'required|in:home,chat',
            'email' => 'nullable|email',
            'payment_status' => 'nullable|in:pending,paid,completed,cancelled',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
