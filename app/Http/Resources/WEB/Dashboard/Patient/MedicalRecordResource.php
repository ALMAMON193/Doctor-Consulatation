<?php

namespace App\Http\Resources\WEB\Dashboard\Patient;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'record_type' => $this->record_type,
            'details' => $this->details,
        ];
    }
}
