<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:coupons,code',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'required|date|before_or_equal:valid_to',
            'valid_to' => 'required|date|after_or_equal:valid_from',
            'usage_limit' => 'required|integer|min:1',
        ];
    }


    public function messages(): array
    {
        return [
            'code.required' => __('Coupon code is required.'),
            'code.unique' => __('This coupon code is already taken.'),
            'discount_percentage.numeric' => __('Discount percentage must be a number.'),
            'discount_percentage.min' => __('Discount percentage cannot be less than 0.'),
            'discount_percentage.max' => __('Discount percentage cannot be greater than 100.'),
            'discount_amount.numeric' => __('Discount amount must be a number.'),
            'discount_amount.min' => __('Discount amount cannot be less than 0.'),
            'valid_from.required' => __('Start date is required.'),
            'valid_from.date' => __('Start date must be a valid date.'),
            'valid_from.before_or_equal' => __('Start date must be before or equal to end date.'),
            'valid_to.required' => __('End date is required.'),
            'valid_to.date' => __('End date must be a valid date.'),
            'valid_to.after_or_equal' => __('End date must be after or equal to start date.'),
            'usage_limit.required' => __('Usage limit is required.'),
            'usage_limit.integer' => __('Usage limit must be an integer.'),
            'usage_limit.min' => __('Usage limit must be at least 1.'),
        ];
    }
}
