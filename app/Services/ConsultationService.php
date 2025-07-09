<?php

namespace App\Services;

use App\Models\{Coupon, DoctorProfile, CouponUser};

class ConsultationService
{
    /**
     * Apply coupon to a doctor's consultation fee for a patient or patient member.
     *
     * @param DoctorProfile $doctor
     * @param string|null $code
     * @param int|null $patientId
     * @param int|null $memberId
     * @return array
     */
    public static function applyCoupon(DoctorProfile $doctor, ?string $code, ?int $patientId = null, ?int $memberId = null): array
    {
        $fee = (float) $doctor->consultation_fee;
        $discount = 0;
        $message = null;

        // No coupon code
        if (!$code) {
            return self::format($fee, 0, $fee);
        }

        // Find active coupon matching code and doctor
        $coupon = Coupon::active()
            ->where('code', $code)
            ->where(function ($query) use ($doctor) {
                $query->whereNull('doctor_profile_id')
                    ->orWhere('doctor_profile_id', $doctor->id);
            })
            ->first();

        if (!$coupon) {
            return self::format($fee, 0, $fee, __('Invalid or expired coupon.'));
        }

        // Check if coupon already used by patient or member
        $used = CouponUser::where('coupon_id', $coupon->id)
            ->where(function ($query) use ($patientId, $memberId) {
                if ($patientId) {
                    $query->where('patient_id', $patientId);
                }
                if ($memberId) {
                    $query->orWhere('patient_member_id', $memberId);
                }
            })
            ->exists();

        if ($used) {
            return self::format($fee, 0, $fee, __('You have already used this coupon.'));
        }

        // Calculate discount
        if ($coupon->discount_percentage > 0) {
            $discount = round($fee * $coupon->discount_percentage / 100, 2);
            $message = "Discount: {$coupon->discount_percentage}% via {$coupon->code}";
        } else {
            $discount = min($coupon->discount_amount, $fee);
            $message = "Discount: \${$discount} via {$coupon->code}";
        }

        // Store coupon usage
        CouponUser::create([
            'coupon_id' => $coupon->id,
            'patient_id' => $patientId,
            'patient_member_id' => $memberId,
            'used_at' => now(),
        ]);

        // Update coupon usage count & status
        $coupon->increment('used_count');
        if ($coupon->used_count >= $coupon->usage_limit) {
            $coupon->update(['status' => 'used']);
        }

        $final = max($fee - $discount, 0);

        return self::format($fee, $discount, $final, null, $coupon->code, $message);
    }

    private static function format($fee, $discount, $final, $error = null, $code = null, $msg = null): array
    {
        return [
            'fee' => $fee,
            'discount' => $discount,
            'final' => $final,
            'error' => $error,
            'coupon_code' => $code,
            'message' => $msg,
        ];
    }
}
