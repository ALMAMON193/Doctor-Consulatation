<?php

namespace App\Http\Requests\APP\Doctor;

use App\Models\DoctorProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'video_path'         => 'nullable|file|mimes:mp4,avi,mov|max:1000240', // 1000MB max
        ];
    }
    public function messages(): array
    {
        return [
            'crm.unique' => 'The CRM has already been taken by another doctor.',
            'video_path.mimes' => __('Video must be a file of type: mp4, avi, mov.'),
            'video_path.max' => __('Video file size must not exceed 1 GB.'),
        ];
    }
}
