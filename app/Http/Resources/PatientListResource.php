<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'email'                   => $this->email,
            'phone_number'            => $this->phone_number,
            'consulted'               => optional($this->patient)->consulted ?? 0,
            'family_member_consulted' => optional($this->patient)->family_member_consulted ?? 0,
            'member_count'            => optional($this->patient)->members_count ?? 0,
            'verification_status'     => optional($this->patient)->verification_status ?? 'N/A',
            'profile_photo'           => optional($this->patient)->profile_photo,
        ];
    }


}
