<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Patient        $patient
 * @property-read DoctorProfile  $doctorProfile
 * @property-read Payment|null   $payment
 * @method static create(array $array)
 * @method static whereIn(string $string, string[] $array)
 * @method static where(string $string, $id)
 */
class Consultation extends Model
{
    // doctor_id নয়, doctor_profile_id ফিল্ড দরকার
    protected $fillable = [
        'patient_id',
        'doctor_profile_id',
        'patient_member_id',
        'fee_amount',
        'coupon_code',
        'discount_amount',
        'final_amount',
        'complaint',
        'pain_level',
        'consultation_date',
        'payment_status'
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
    public function patientMember(): BelongsTo
    {
        return $this->belongsTo(PatientMember::class, 'patient_member_id');
    }

    protected static function booted(): void
    {
        static::created(function ($consultation) {
            // if payment status paid
            if ($consultation->payment_status === 'paid') {
                // if  patient make a consultation
                if ($consultation->patient_id) {
                    $consultation->patient?->increment('consulted');
                }
                // if member make a consultation
                elseif ($consultation->patient_member_id) {
                    $consultation->patientMember?->patient?->increment('consulted');
                }
            }
        });
    }
}
