<?php

namespace App\Http\Requests\APP\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'consultation_id' => 'required|exists:consultations,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'consultation_id.required' => 'Consultation is required.',
            'consultation_id.exists' => 'Consultation not found.',
            'rating.required' => 'Rating is required.',
            'rating.integer' => 'Rating must be numeric.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating cannot exceed 5.',
            'review.max' => 'Review cannot be longer than 1000 characters.',
        ];
    }
}
