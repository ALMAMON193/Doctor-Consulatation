<?php

namespace App\Http\Resources\WEB\Dashboard\Coupon;

use Illuminate\Http\Resources\Json\JsonResource;

class ListCouponResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'status' => $this->status,
        ];
    }
}
