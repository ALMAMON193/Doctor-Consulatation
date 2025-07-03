<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $account_type
 * @property mixed $gender
 * @property mixed $email
 * @property mixed $phone
 * @property mixed $annual_income_company
 * @property mixed $name
 */
class DoctorEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow all authenticated users
    }

    public function rules(): array
    {
        $userId = auth('sanctum')->id(); // or auth()->id();

        return [
            // Account Info
            'name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $userId,
            'phone'      => 'nullable|string|max:20',
            'avatar'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Personal Info
            'date_of_birth' => 'nullable|date',
            'cpf'           => 'nullable|string|unique:user_personal_details,cpf,' . $userId . ',user_id',
            'gender'        => 'nullable|in:male,female,other',
            'account_type'  => 'nullable|in:individual,legalEntity',

            // Legal Info
            'monthly_income'           => 'nullable|numeric',
            'annual_income_company'    => 'nullable|numeric',
            'company_phone'            => 'nullable|string|max:20',
            'company_name'             => 'nullable|string|max:255',

            // Address Info
            'zipcode'        => 'nullable|string|max:20',
            'number'         => 'nullable|string|max:20',
            'street'         => 'nullable|string|max:255',
            'neighborhood'   => 'nullable|string|max:255',
            'complement'     => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',

            // Medical Info
            'crm'            => 'nullable|string|max:50',
            'uf'             => 'nullable|string|max:10',
            'specialization' => 'nullable|string|max:255',
            'video'          => 'nullable|mimetypes:video/mp4,video/mpeg|max:20000',

            // Financial Info
            'cpf_bank'       => 'nullable|string|max:20',
            'bank_name'      => 'nullable|string|max:100',
            'account_type_bank' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:30',
            'dv'             => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already in use.',
            'cpf.unique'   => 'This CPF is already registered.',
            'avatar.image' => 'Avatar must be a valid image (jpg, jpeg, png).',
            'video.mimetypes' => 'The video must be a file of type: mp4, mpeg.',
        ];
    }
}
