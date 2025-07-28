<?php

namespace App\Http\Requests;

use App\Models\DoctorProfile;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MedicalInfoVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        $user = auth('sanctum')->user();
        $doctorProfileId = $user ? DoctorProfile::where('user_id', $user->id)->first()?->id : null;
        return [
            'specialization' => 'required|array|max:7',
            'specialization.*' => 'string|exists:specializations,name',
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
            'current_account_number' => 'required|string|max:255',
            'current_dv' => 'required|string|max:255',
            'uf' => 'required|string|max:2',
            'zipcode' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:255',
            'road_number' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'complement' => 'nullable|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'crm.unique' => 'The CRM has already been taken by another doctor.',
        ];
    }
}
