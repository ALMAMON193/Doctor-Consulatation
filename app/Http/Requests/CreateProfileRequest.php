<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProfileRequest extends FormRequest
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
            'date_of_birth' => 'required|date',
            'cpf' => 'required|string|unique:user_personal_details,cpf|max:30',
            'gender' => 'required|in:male,female,other',
            'account_type' => 'nullable|in:individual,legalEntity',
            'monthly_income' => 'nullable|string',
            'annual_income_for_company' => 'nullable|string',
            'company_telephone_number' => 'nullable|string',
            'business_name' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.unique' => 'This CPF is already registered.',
            'gender.in' => 'Please select a valid gender.',
            'account_type.in' => 'Please select a valid account type.',
        ];
    }
}
