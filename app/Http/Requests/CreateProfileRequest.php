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

    public function rules()
    {
        return [
            'date_of_birth' => 'required|date',
            'cpf' => 'required|string|unique:user_personal_details,cpf|max:30',
            'gender' => 'required|in:male,female,other',
            'account_type' => 'required|in:individual,legalEntity',
            'monthly_income' => 'required|string',
            'annual_income_for_company' => 'required|string',
            'company_telephone_number' => 'required|string',
            'business_name' => 'required|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
        ];
    }

    public function messages()
    {
        return [
            'cpf.unique' => 'This CPF is already registered.',
            'gender.in' => 'Please select a valid gender.',
            'account_type.in' => 'Please select a valid account type.',
            'monthly_income.required' => 'The monthly income is required.',
            'annual_income_for_company.required' => 'The annual income for company is required.',
            'company_telephone_number.required' => 'The company telephone number is required.',
            'business_name.required' => 'The business name is required.',
        ];
    }
}
