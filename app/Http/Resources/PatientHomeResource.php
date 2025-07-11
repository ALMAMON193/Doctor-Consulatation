<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientHomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'active_doctors' => [], // Replace with actual data
            'specialist_online' => [], // Replace with actual data
            'your_consultation' => [], // Replace with actual data

            'complete_registration' => [
                'next_step' => [], // Replace with actual data
                'complete' => [], // Replace with actual data
            ],

            'all_specialists' => [
                'specialization' => [], // Replace with actual data
            ],
        ];
    }
}
