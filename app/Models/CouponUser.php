<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUser extends Model
{
    protected $fillable = [
        'coupon_id', 'patient_id', 'patient_member_id', 'used_at'
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientMember()
    {
        return $this->belongsTo(PatientMember::class);
    }
}
