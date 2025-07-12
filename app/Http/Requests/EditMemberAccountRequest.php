<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditMemberAccountRequest extends FormRequest
{
    /**
     * Only authenticated patients can update member info.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check()
            && auth('sanctum')->user()->user_type === 'patient';
    }

    /**
     * Validation rules for updating a patient member.
     */
    public function rules(): array
    {
        $memberId = $this->route('id'); // This must match your route: /members/{id}
        $patientId = optional($this->user()->patient)->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'relationship' => ['required', 'string', 'max:100'],

            'cpf' => [
                'nullable',
                'string',
                'max:30',
                // Unique per patient_id and ignore own row
                Rule::unique('patient_members', 'cpf')
                    ->where(function ($query) use ($patientId) {
                        $query->where('patient_id', $patientId);
                    })
                    ->ignore($memberId),
            ],
            'gender' => ['nullable', 'in:male,female,other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:20480'], // 20 MB max
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'cpf.unique' => __('The CPF is already registered for another member.'),
            'date_of_birth.before' => __('The date of birth must be a valid date before today.'),
            'profile_photo.image' => __('The profile photo must be an image (jpeg, png, jpg).'),
            'profile_photo.max' => __('The profile photo may not be larger than 20 MB.'),
        ];
    }
}
