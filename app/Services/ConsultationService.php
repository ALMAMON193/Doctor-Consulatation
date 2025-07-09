<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\DoctorProfile;

class ConsultationService
{
    /**
     * Calculate consultation fee applying coupon if valid.
     *
     * @param DoctorProfile $doctor
     * @param string|null $couponCode
     * @return array
     */
    public static function calculateFee(DoctorProfile $doctor, ?string $couponCode): array
    {
        $fee = (float) $doctor->consultation_fee;
        $discount = 0;
        $error = null;
        $message = null;

        if ($couponCode) {
            $coupon = Coupon::active()
                ->where('code', $couponCode)
                ->where(function ($q) use ($doctor) {
                    // Coupon either global (null doctor) or for this doctor
                    $q->whereNull('doctor_profile_id')
                        ->orWhere('doctor_profile_id', $doctor->id);
                })
                ->first();

            if (!$coupon) {
                return [
                    'fee'         => $fee,
                    'discount'    => 0,
                    'final'       => $fee,
                    'error'       => __('Invalid or expired coupon.'),
                    'coupon_code' => null,
                    'message'     => null,
                ];
            }

            if ($coupon->discount_percentage > 0) {
                $discount = round($fee * ($coupon->discount_percentage / 100), 2);
                $message = "Discount: {$coupon->discount_percentage}% via {$coupon->code}";
            } else {
                // Fixed amount discount capped at fee
                $discount = min($coupon->discount_amount, $fee);
                $message = "Discount: \${$discount} via {$coupon->code}";
            }

            // Increment usage count and update coupon status if limit reached
            $coupon->increment('used_count');
            if ($coupon->used_count >= $coupon->usage_limit) {
                $coupon->update(['status' => 'used']);
            }
        }

        $final = max($fee - $discount, 0);

        return [
            'fee'         => $fee,
            'discount'    => $discount,
            'final'       => $final,
            'error'       => $error,
            'coupon_code' => $couponCode,
            'message'     => $message,
        ];
    }
}
