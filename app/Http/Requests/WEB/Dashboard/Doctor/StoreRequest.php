<?php

namespace App\Http\Requests\WEB\Dashboard\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email,' . auth('sanctum')->user()->id,
            'phone_number'          => 'required|string|max:20',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required'             => 'Name is required',
            'email.required'            => 'Email is required',
            'email.email'               => 'Email is invalid',
            'email.max'                 => 'Email is too long',
            'email.unique'              => 'Email is invalid',
            'phone_number.required'     => 'Phone number is required',
            'phone_number.integer'      => 'Phone number is invalid',
            'phone_number.unique'       => 'Phone number is invalid',
            'password.required'         => 'Password is required',
            'password.min'              => 'Password is too short',
            'password.confirmed'        => 'Password does not match',
        ];
    }
}
