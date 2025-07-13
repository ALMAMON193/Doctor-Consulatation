<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for validating doctor profile details update.
 * @property mixed $date_of_birth
 * @property mixed $cpf
 * @property mixed $gender
 * @property mixed $account_type
 * @property mixed $name
 * @property mixed $email
 * @property mixed $phone
 * @property mixed $phone_number
 */
class DoctorProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth('sanctum')->user() && auth('sanctum')->user()->user_type === 'doctor';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email,' . auth('sanctum')->user()->id,
            'phone_number'         => 'required|string|max:20',
            'avatar'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'date_of_birth' => 'required|date',
            'cpf'           => 'required|string|max:20',
            'gender'        => 'required|string|in:male,female,other',
            'account_type'  => 'required|string|in:individual,legalEntity',
        ];
    }
}
