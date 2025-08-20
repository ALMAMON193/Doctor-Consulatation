<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSpecialization extends Model
{
    protected $fillable = [
        'doctor_id',
        'specialization_id',
    ];

    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_id');
    }
    public function specialization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Specialization::class, 'specialization_id');
    }
}
