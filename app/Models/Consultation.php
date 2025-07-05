<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Patient        $patient
 * @property-read DoctorProfile  $doctorProfile
 * @property-read Payment|null   $payment
 */
class Consultation extends Model
{
    // doctor_id নয়, doctor_profile_id ফিল্ড দরকার
    protected $fillable = [
        'patient_id',
        'doctor_profile_id',
        'fee_amount',
        'coupon_code',
        'discount_amount',
        'final_amount',
        'complaint',
        'pain_level',
        'consultation_date',
    ];

    /* Relationships ------------------------------------------------------- */

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
