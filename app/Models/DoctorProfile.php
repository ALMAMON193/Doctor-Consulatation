<?php

namespace App\Models;

use App\Models\User;
use App\Models\Rating;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HigherOrderCollectionProxy;

/**
 * @method static firstOrCreate(array $array)
 * @method static where(string $string, $id)
 * @method static whereNotNull(string $string)
 * @method static findOrFail(mixed $doctor_profile_id)
 * @property HigherOrderCollectionProxy|mixed $specialization_id
 */
class DoctorProfile extends Model
{
    protected $fillable = [
        'user_id', 'specialization', 'cpf_bank', 'bank_name', 'account_type',
        'account_number', 'dv', 'current_account_number', 'current_dv',
        'crm', 'uf', 'consultation_fee', 'consultation_time',
        'monthly_income', 'company_income', 'company_phone', 'company_name',
        'zipcode', 'address', 'house_number', 'road_number', 'street',
        'neighborhood', 'city', 'state', 'complement', 'personal_name',
        'date_of_birth', 'cpf_personal', 'email', 'phone_number',
        'video_path', 'profile_picture', 'verification_status',
        'verification_rejection_reason', 'bio'
    ];

    protected $casts = [
        'specialization' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class, 'doctor_id');
    }

    public function consultations(): \Illuminate\Database\Eloquent\Relations\HasMany|DoctorProfile
    {
        return $this->hasMany(\App\Models\Consultation::class, 'doctor_id');
    }
    public function specializations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Specialization::class, 'doctor_specializations', 'doctor_id', 'specialization_id');
    }

}
