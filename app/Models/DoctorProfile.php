<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static firstOrCreate(array $array)
 * @method static where(string $string, $id)
 */
class DoctorProfile extends Model
{

    protected $fillable = [
        'user_id',
        'additional_medical_record_number',
        'specialization',
        'cpf_bank',
        'bank_name',
        'account_type',
        'account_number',
        'dv',
        'crm',
        'uf',
        'monthly_income',
        'company_income',
        'company_phone',
        'company_name',
        'address_zipcode',
        'address_number',
        'address_street',
        'address_neighborhood',
        'address_city',
        'address_state',
        'address_complement',
        'personal_name',
        'date_of_birth',
        'cpf_personal',
        'email',
        'phone_number',
        'video_path',
        'verification_status',
        'verification_rejection_reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
