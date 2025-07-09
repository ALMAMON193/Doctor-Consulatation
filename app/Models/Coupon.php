<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_percentage',
        'discount_amount',
        'doctor_profile_id',
        'valid_from',
        'valid_to',
        'usage_limit',
        'used_count',
        'status',
    ];

    public function doctor()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function couponUsers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CouponUser::class);
    }

    public function isValid(): bool
    {
        $now = now();
        return $this->status === 'active' &&
            $now->between($this->valid_from, $this->valid_to) &&
            $this->used_count < $this->usage_limit;
    }

    public function applyDiscount($amount)
    {
        if ($this->isValid()) {
            if ($this->discount_percentage > 0) {
                return $amount * ($this->discount_percentage / 100);
            } elseif ($this->discount_amount > 0) {
                return min($this->discount_amount, $amount);
            }
        }
        return 0;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereDate('valid_from', '<=', Carbon::today())
            ->whereDate('valid_to', '>=', Carbon::today())
            ->whereColumn('used_count', '<', 'usage_limit');
    }


}
