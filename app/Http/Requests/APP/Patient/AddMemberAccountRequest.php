<?php

namespace App\Http\Requests\APP\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AddMemberAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow only authenticated users
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'relationship'  => 'nullable|string|max:255',
            'cpf'           => 'nullable|string|max:20',
            'gender'        => 'nullable|in:male,female,other',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:20048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'gender.in' => 'Gender must be one of: male, female, or other.',
            'profile_photo.image' => 'Profile photo must be an image.',
        ];
    }
}
