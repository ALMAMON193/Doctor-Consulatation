<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Coupon;
use App\Models\DoctorProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationService
{
    /**
     * Create a consultation and apply a valid coupon if provided.
     *
     * @param array $data
     * @return Consultation
     * @throws ValidationException
     */
    public function create(array $data): Consultation
    {
        return DB::transaction(function () use ($data) {
            $doctor = DoctorProfile::findOrFail($data['doctor_profile_id']);
            $fee = (float) $doctor->consultation_fee;

            $discount = 0;
            $couponCode = $data['coupon_code'] ?? null;

            if ($couponCode) {
                $coupon = Coupon::active()
                    ->where('code', $couponCode)
                    ->where(function ($q) use ($doctor) {
                        $q->whereNull('doctor_profile_id')
                            ->orWhere('doctor_profile_id', $doctor->id);
                    })
                    ->first();

                if (! $coupon) {
                    throw ValidationException::withMessages([
                        'coupon_code' => __('Invalid or expired coupon.'),
                    ]);
                }

                $discount = $coupon->discount_percentage
                    ? round($fee * ($coupon->discount_percentage / 100), 2)
                    : min($coupon->discount_amount, $fee);

                $coupon->increment('used_count');
                if ($coupon->used_count >= $coupon->usage_limit) {
                    $coupon->update(['status' => 'used']);
                }
            }

            $final = max($fee - $discount, 0);

            return Consultation::create([
                'patient_id'        => $data['patient_id'],
                'doctor_profile_id' => $doctor->id,
                'fee_amount'        => $fee,
                'coupon_code'       => $couponCode,
                'discount_amount'   => $discount,
                'final_amount'      => $final,
                'complaint'         => $data['complaint'] ?? null,
                'pain_level'        => $data['pain_level'] ?? 0,
                'consultation_date' => $data['consultation_date'] ?? now(),
            ]);
        });
    }
}
