<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(mixed $data)
 */
class Rating extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_profile_id',
        'patient_member_id',
        'rating',
        'review',
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
