<?php

namespace App\Http\Requests\APP\Patient;

use Illuminate\Foundation\Http\FormRequest;

class MedicalRecordStoreRequest extends FormRequest
{
    public function authorize(): true
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'record_type'        => ['required', 'string'],
            'record_date'        => ['nullable', 'date'],
            'file_path'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png'],
            'patient_member_id'  => ['nullable', 'exists:patient_members,id'],
        ];
    }
}
