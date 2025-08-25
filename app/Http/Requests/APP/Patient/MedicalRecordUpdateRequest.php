<?php

namespace App\Http\Requests\APP\Patient;

use Illuminate\Foundation\Http\FormRequest;

class MedicalRecordUpdateRequest extends FormRequest
{
    public function authorize(): true
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules(): array
    {
        return [
            'record_type' => 'sometimes|string|max:255',
            'record_date' => 'sometimes|date',
            'file_path' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:102400',
        ];
    }
}
