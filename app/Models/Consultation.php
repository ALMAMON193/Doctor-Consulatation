<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    protected $fillable = [
        'patient_id',
        'patient_member_id',
        'specialization_id',
        'fee_amount',
        'discount_amount',
        'final_amount',
        'complaint',
        'pain_level',
        'consultation_date',
        'consultation_type',
        'payment_status',
        'doctor_id',
        'consultation_status',
        'assign_application',
        'assign_at',
    ];

    protected $casts = [
        'consultation_date' => 'datetime',
        'assign_at' => 'datetime',
        'pain_level' => 'integer',
        'fee_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    /* 🔹 Relationships */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function patientMember(): BelongsTo
    {
        return $this->belongsTo(PatientMember::class, 'patient_member_id');
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id');
    }
    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'consultation_id', 'id');
    }

    /* 🔹 Useful Attribute Helpers */
    public function getNotifiableUserAttribute()
    {
        if ($this->patient?->user) {
            return $this->patient->user;
        }
        if ($this->patientMember?->patient?->user) {
            return $this->patientMember->patient->user;
        }
        return null;
    }

    public function getPatientNameAttribute(): string
    {
        return $this->patient?->user?->name
            ?? $this->patientMember?->patient?->user?->name
            ?? $this->patientMember?->name
            ?? 'Unknown';
    }

    public function getDoctorNameAttribute(): ?string
    {
        return $this->doctorProfile?->user?->name;
    }

    public function getSpecializationNameAttribute(): string
    {
        return $this->specialization?->name ?? 'General';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment && in_array($this->payment->status, ['completed', 'paid']);
    }
}
