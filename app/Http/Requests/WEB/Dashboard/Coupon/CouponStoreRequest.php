<?php

namespace App\Http\Requests\WEB\Dashboard\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class CouponStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // <-- This must be true to allow requests
    }

    public function rules(): array
    {
        return [
            'code' => 'required|unique:coupons,code',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after_or_equal:valid_from',
            'usage_limit' => 'required|integer|min:1',
        ];
    }
}
