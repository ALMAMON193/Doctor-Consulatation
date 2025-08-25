<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'doctor_profile_id',
        'amount',
        'transaction_id',
        'account_name',
        'account_number',
        'remarks',
        'status',
        'approved_at',
        'rejected_at',
    ];

    public function doctorProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }
}
