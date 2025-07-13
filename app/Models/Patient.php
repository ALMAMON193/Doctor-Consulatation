<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static firstOrCreate(array $array)
 */
class Patient extends Model
{
    protected $table = 'patients';
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'cpf',
        'gender',
        'mother_name',
        'zipcode',
        'house_number',
        'road',
        'neighborhood',
        'complement',
        'city',
        'state',
        'profile_photo',
        'consulted',
        'family_member_of_patient',
        'verification_status',
        'verification_rejection_reason'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function patientMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientMember::class, 'patient_id');
    }
    public function medicalRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientMedicalRecord::class);
    }
    public function members(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientMember::class, 'patient_id');
    }
}
