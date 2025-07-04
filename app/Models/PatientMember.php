<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMember extends Model
{
    protected $table = 'patient_members';

    protected $fillable = [
        'patient_id',
        'name',
        'gender',
        'date_of_birth',
        'cpf',
        'relationship',
        'profile_photo'
    ];
    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
    public function medicalRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientMedicalRecord::class);
    }
}
