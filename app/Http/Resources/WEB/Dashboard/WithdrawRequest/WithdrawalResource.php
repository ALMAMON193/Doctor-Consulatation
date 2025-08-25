<?php

namespace App\Http\Resources\WEB\Dashboard\WithdrawRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_profile' => [
                'id' => $this->doctorProfile->id,
                'name' => $this->doctorProfile->user->name ?? null,
                'email' => $this->doctorProfile->user->email ?? null,
            ],
            'amount' => $this->amount,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
