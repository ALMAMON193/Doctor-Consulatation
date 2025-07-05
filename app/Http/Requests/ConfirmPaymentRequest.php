<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_intent_id' => ['required', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
