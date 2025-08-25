<?php

namespace App\Http\Resources\WEB\Dashboard\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'valid_from' => $this->valid_from->toDateString(),
            'valid_to' => $this->valid_to->toDateString(),
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'status' => $this->status,

            'usage_details' => $this->couponUsers->map(function ($usage) {
                return [
                    'used_at' => $usage->used_at,
                    'patient' => $usage->patient ? [
                        'id' => $usage->patient->id,
                        'name' => $usage->patient->user->name ?? null,
                        'email' => $usage->patient->user->email ?? null,
                    ] : null,
                    'patient_member' => $usage->patientMember ? [
                        'id' => $usage->patientMember->id,
                        'name' => $usage->patientMember->name,
                        'relationship' => $usage->patientMember->relationship,
                    ] : null,
                ];
            }),
            'total_used' => $this->couponUsers->count(),
            'remaining_uses' => $this->usage_limit - $this->used_count,
        ];
    }
}
