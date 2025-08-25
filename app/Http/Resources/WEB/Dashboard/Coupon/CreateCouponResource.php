<?php

namespace App\Http\Resources\WEB\Dashboard\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreateCouponResource extends JsonResource
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
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
