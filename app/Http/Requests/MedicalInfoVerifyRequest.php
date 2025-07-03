<?php

namespace App\Http\Requests;

use App\Models\DoctorProfile;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MedicalInfoVerifyRequest extends FormRequest
{
    public function authorize()
    {
        return auth('sanctum')->check();
    }

    public function rules()
    {
        $user = auth('sanctum')->user();
        $doctorProfileId = $user ? DoctorProfile::where('user_id', $user->id)->first()?->id : null;
        return [
            'additional_medical_record_number' => 'nullable|string|max:255',
            'specialization' => 'required|string|max:255',
            'cpf_bank' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_type' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'dv' => 'required|string|max:255',
            'crm' => [
                'required',
                'string',
                'max:255',
                Rule::unique('doctor_profiles', 'crm')->ignore($doctorProfileId),
            ],
            'uf' => 'required|string|max:2',
            'monthly_income' => 'required|numeric|min:0',
            'company_income' => 'required|numeric|min:0',
            'company_phone' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'address_zipcode' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:255',
            'address_street' => 'nullable|string|max:255',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:255',
            'address_complement' => 'nullable|string|max:255',
            'personal_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'cpf_personal' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:255',
            'video_path' => 'nullable|file|mimes:mp4,mov,avi|max:102400', // Example: 100MB max
        ];
    }
    public function messages()
    {
        return [

            'crm.unique' => 'The CRM has already been taken by another doctor.',

        ];
    }
}
