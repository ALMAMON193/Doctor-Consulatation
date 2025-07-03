<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for validating doctor financial information update.
 * @property mixed $cpf_bank
 * @property mixed $bank_name
 * @property mixed $account_type
 * @property mixed $account_number
 * @property mixed $dv
 */
class DoctorFinancialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('sanctum')->user() && auth('sanctum')->user()->user_type === 'doctor';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cpf_bank'       => 'required|string|max:20',
            'bank_name'      => 'required|string|max:100',
            'account_type'   => 'required|string|in:checking,savings',
            'account_number' => 'required|string|max:50',
            'dv'             => 'required|string|max:10',
        ];
    }
}
