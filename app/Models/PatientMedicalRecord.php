<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMedicalRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'patient_member_id',
        'record_type',
        'record_date',
        'file_path',
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PatientMember::class, 'patient_member_id');
    }
}
