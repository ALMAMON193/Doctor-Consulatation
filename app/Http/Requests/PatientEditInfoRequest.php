<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientEditInfoRequest extends FormRequest
{
    /**
     * Authorize only logged-in patients.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check() &&
            auth('sanctum')->user()->user_type === 'patient';
    }

    /**
     * Validation rules for editing patient profile.
     */
    public function rules(): array
    {
        $user = $this->user();                        // Authenticated user
        $patient = optional($user->patient);          // Get linked patient

        return [
            // ── Account Info ─────────────────────────────
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'  => ['required', 'string', 'max:20'],

            // ── Personal Info ────────────────────────────
            'date_of_birth' => ['required', 'date', 'before:today'],

            // ✅ Fix: match cpf uniqueness by user_id
            'cpf' => [
                'required',
                'string',
                'max:30',
                Rule::unique('patients', 'cpf')->ignore($user->id, 'user_id'),
            ],

            'gender'      => ['required', 'in:male,female,other'],
            'mother_name' => ['required', 'string', 'max:255'],

            // ── Address Info ─────────────────────────────
            'zipcode'      => ['required', 'string', 'max:10'],
            'house_number' => ['required', 'string', 'max:255'],
            'road'         => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'complement'   => ['required', 'string', 'max:255'],
            'city'         => ['required', 'string', 'max:255'],
            'state'        => ['required', 'string', 'max:255'],

            // ── Profile Photo ────────────────────────────
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:20480'], // 20 MB max
        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'cpf.unique'               => __('The CPF is already registered.'),
            'email.unique'             => __('This email is already taken.'),
            'date_of_birth.before'     => __('The date of birth must be a valid date before today.'),
            'profile_photo.image'      => __('The profile photo must be an image (jpeg, png, jpg).'),
            'profile_photo.max'        => __('The profile photo may not be larger than 20 MB.'),
        ];
    }
}
