<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'consultation_id' => 'required|exists:consultations,id',
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:5000',
            'file' => 'nullable|file|mimes:jpg,png,pdf,doc,docx|max:10240', // Max 10MB
        ];
    }
}
