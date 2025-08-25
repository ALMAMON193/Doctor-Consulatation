<?php

namespace App\Http\Requests\APP\Doctor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for validating doctor medical information update.
 * @property mixed $specialization
 * @property mixed $uf
 * @property mixed $crm
 */
class DoctorMedicalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('sanctum')->user() && auth('sanctum')->user()->user_type === 'doctor';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'crm'           => 'required|string|max:50',
            'uf'            => 'required|string|max:50',
            'specialization' => 'required|string|max:255',
            'video_path'         => 'nullable|file|mimes:mp4,avi,mov|max:1000240', // 1000MB max
        ];
    }

    public function messages(): array
    {
        return [
            'crm.required' => __('CRM is required.'),
            'uf.required' => __('UF is required.'),
            'specialization.required' => __('Specialization is required.'),
            'video_path.mimes' => __('Video must be a file of type: mp4, avi, mov.'),
            'video_path.max' => __('Video file size must not exceed 1 GB.'),
        ];
    }
}
