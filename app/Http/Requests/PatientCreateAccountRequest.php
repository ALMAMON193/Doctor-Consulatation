<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PatientCreateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'date_of_birth' => ['required', 'date_format:Y-m-d'],
            'cpf' => ['required', 'unique:patients'],
            'gender' => ['required', 'in:male,female,other'],
            'mother_name' => ['required', 'string', 'max:255'],
            'zipcode' => ['required', 'string', 'max:10'],
            'house_number' => ['required', 'string', 'max:10'],
            'road' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'complement' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'profile_photo' => [
                'nullable',
                'mimes:jpg,jpeg,png,gif,webp,bmp,svg',
                'max:20480' // Max size in KB (20MB)
            ],
        ];
    }
}
