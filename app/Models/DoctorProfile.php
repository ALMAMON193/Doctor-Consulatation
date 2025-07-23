<?php

namespace App\Models;

use App\Models\User;
use App\Models\Rating;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static firstOrCreate(array $array)
 * @method static where(string $string, $id)
 * @method static whereNotNull(string $string)
 * @method static findOrFail(mixed $doctor_profile_id)
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
        'consultation_fee',
        'consultation_time',
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
        'profile_picture',
        'verification_status',
        'verification_rejection_reason'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class);
    }
    public function completedConsultations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Consultation::class)->where('consultation_status', 'completed');
    }


}
