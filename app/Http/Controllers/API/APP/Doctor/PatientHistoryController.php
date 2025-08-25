<?php

namespace App\Http\Controllers\API\APP\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\APP\Doctor\Consultation\AvailableResource;
use App\Models\Consultation;
use App\Traits\ApiResponse;

class PatientHistoryController extends Controller
{
    use ApiResponse;
    public function patientHistory(){
        $user = auth ()->user ();
        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }
        // Get the doctor's profile ID (assumes one-to-one relation from user to doctorProfile)
        $doctorProfileId = $user->doctorProfile->id ?? null;
        if (!$doctorProfileId) {
            return $this->sendError('Doctor profile not found', [], 404);
        }
        // Fetch consultations where payment_status = paid and belongs to this doctor
        $consultations = Consultation::with(['patient', 'patientMember'])
            ->where('doctor_profile_id', $doctorProfileId)
            ->get();
        return $this->sendResponse(
            AvailableResource::collection($consultations),
            __('Patient History Get Successfully')
        );
    }

}
