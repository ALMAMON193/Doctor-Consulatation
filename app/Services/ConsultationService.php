<?php

namespace App\Services;

use App\Models\{Coupon, CouponUser};

class ConsultationService
{
    /**
     * Apply coupon to a consultation fee.
     *
     * @param string|null $code
     * @param int|null $patientId
     * @param int|null $memberId
     * @param float|null $feeAmount
     * @return array
     */
    public static function applyCoupon(
        ?string $code,
        ?int $patientId = null,
        ?int $memberId = null,
        ?float $feeAmount = null
    ): array {
        $fee = $feeAmount ?? 0; // Use specialization fee
        $discount = 0; // Initialize discount
        $message = null; // Initialize message

        if (!$code) return self::format($fee, 0, $fee); // No coupon

        $coupon = Coupon::where('code', $code) // Find active coupon
        ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now())
            ->first();

        if (!$coupon) return self::format($fee, 0, $fee, __('Invalid or expired coupon.')); // Invalid coupon

        $used = CouponUser::where('coupon_id', $coupon->id) // Check usage
        ->where(function ($query) use ($patientId, $memberId) {
            if ($patientId) $query->where('patient_id', $patientId);
            if ($memberId) $query->orWhere('patient_member_id', $memberId);
        })
            ->exists();

        if ($used) return self::format($fee, 0, $fee, __('You have already used this coupon.')); // Already used

        if ($coupon->discount_percentage > 0) { // Percentage discount
            $discount = round($fee * $coupon->discount_percentage / 100, 2);
            $message = "Discount: {$coupon->discount_percentage}% via {$coupon->code}";
        } else { // Fixed discount
            $discount = min($coupon->discount_amount, $fee);
            $message = "Discount: \${$discount} via {$coupon->code}";
        }

        CouponUser::create([ // Save usage
            'coupon_id' => $coupon->id,
            'patient_id' => $patientId,
            'patient_member_id' => $memberId,
            'used_at' => now(),
        ]);

        $coupon->increment('used_count'); // Increment used count
        if ($coupon->used_count >= $coupon->usage_limit) $coupon->update(['status' => 'used']); // Mark used

        $final = max($fee - $discount, 0); // Calculate final fee

        return self::format($fee, $discount, $final, null, $coupon->code, $message); // Return details
    }

    private static function format($fee, $discount, $final, $error = null, $code = null, $msg = null): array
    {
        return [ // Format response
            'fee' => $fee,
            'discount' => $discount,
            'final' => $final,
            'error' => $error,
            'coupon_code' => $code,
            'message' => $msg,
        ];
    }
}
