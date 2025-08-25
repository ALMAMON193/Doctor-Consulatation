<?php

namespace App\Http\Controllers\API\APP\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\APP\Doctor\Consultation\AvailableResource;
use App\Models\Consultation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PatientHistoryController extends Controller
{
    use ApiResponse;

    public function patientHistory(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }

        $doctorProfileId = $user->doctorProfile->id ?? null;
        if (!$doctorProfileId) {
            return $this->sendError('Doctor profile not found', [], 404);
        }

        // filter
        $statuses = $request->query('consultation_status', []);
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }

        $query = Consultation::with(['patient', 'patientMember', 'specialization', 'doctorProfile'])
            ->where('doctor_id', $doctorProfileId);

        if (!empty($statuses)) {
            $query->whereIn('consultation_status', $statuses);
        }

        $consultations = $query->latest()->get();

        $totalConsultation = Consultation::where('doctor_id', $doctorProfileId)
            ->where('consultation_status', 'monitoring')
            ->count();

        return $this->sendResponse(
            [
                'available_consultation' => [
                    'total_consultation' => $totalConsultation
                ],
                'patient_history' => AvailableResource::collection($consultations),
            ],
            __('Patient History Get Successfully')
        );
    }
}
