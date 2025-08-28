<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(mixed $data)
 */
class Rating extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'patient_member_id',
        'given_by_id',
        'given_by_type',
        'rating',
        'review'
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctorProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }

    public function patientMember(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PatientMember::class, 'patient_member_id');
    }

}
