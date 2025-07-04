<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray($request): array
    {
        return [
            'id'           =>$this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'specialty'    => optional($this->doctorProfile)->specialization,
            'consulted'    => optional($this->doctorProfile)->consulted ?? 0,
            'subscription' => optional($this->doctorProfile)->subscription ?? 'No Sub',
            'status'       => optional($this->doctorProfile)->verification_status,
        ];
    }
}
