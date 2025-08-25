<?php

namespace App\Http\Requests\APP\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_profile_id' => 'required|exists:doctor_profiles,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'patient_id' => 'nullable|exists:users,id',
            'patient_member_id' => 'nullable|exists:patient_members,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $patientId = $this->input('patient_id');
            $patientMemberId = $this->input('patient_member_id');

            if (empty($patientId) && empty($patientMemberId)) {
                $validator->errors()->add('patient_id', 'Either patient_id or patient_member_id is required.');
                $validator->errors()->add('patient_member_id', 'Either patient_id or patient_member_id is required.');
            }

            if (!empty($patientId) && !empty($patientMemberId)) {
                $validator->errors()->add('patient_id', 'You cannot provide both patient_id and patient_member_id.');
                $validator->errors()->add('patient_member_id', 'You cannot provide both patient_id and patient_member_id.');
            }
        });
    }


    public function messages(): array
    {
        return [
            'doctor_profile_id.required' => 'Doctor is required.',
            'doctor_profile_id.exists'   => 'Selected doctor not found.',
            'rating.required'            => 'Rating is required.',
            'rating.integer'             => 'Rating must be a number.',
            'rating.min'                 => 'Rating must be at least 1 star.',
            'rating.max'                 => 'Rating cannot exceed 5 stars.',
            'review.max'                => 'Review cannot be longer than 1000 characters.',
            'patient_id.exists'          => 'Invalid patient.',
            'patient_member_id.exists'   => 'Invalid patient member.',
        ];
    }
}
