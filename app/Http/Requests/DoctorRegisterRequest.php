<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DoctorRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string|max:20',
            'user_type' => 'required|in:doctor,patient',
            'terms_and_conditions' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'full_name.required' => 'The full name is required.',
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.in' => 'Please select a valid role.',
            'terms_and_conditions.required' => 'Please accept the terms and conditions.',
        ];
    }
}
