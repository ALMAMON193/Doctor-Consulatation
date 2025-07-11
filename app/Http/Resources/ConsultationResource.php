<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $doctorProfile
 * @property mixed $consultation_date
 */
class ConsultationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'doctor_name'        => optional($this->doctorProfile->user)->name,
            'doctor_image' => optional($this->doctorProfile->user)->profile_picture
                ? asset(optional($this->doctorProfile->user)->profile_picture)
                : '',
            'rating'             => $this->averageRatting(),
            'crm'    => $this->doctorProfile->crm ?? null,
            'consultation_date'  => $this->consultation_date
                ? Carbon::parse($this->consultation_date)->format('d/m/Y')
                : null,
        ];
    }

    public function averageRatting(): string
    {

        if (!$this->doctorProfile) {
            return '0.0';
        }
        $avg = $this->doctorProfile->ratings()->avg('rating');
        return number_format($avg ?? 0, 1, '.', '');
    }

}

