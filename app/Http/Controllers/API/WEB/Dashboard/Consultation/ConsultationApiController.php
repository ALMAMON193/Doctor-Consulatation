<?php

namespace App\Http\Controllers\API\WEB\Dashboard\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Resources\WEB\Dashboard\Consultation\ConsultationDetailResource;
use App\Http\Resources\WEB\Dashboard\Consultation\ConsultationListResource;
use App\Models\Consultation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ConsultationApiController extends Controller
{
    Use ApiResponse;

    public function consultationList(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        //  Get analytics summary
        $analytics = $this->getDoctorAnalytics();
        // Get paginated consultations with relationships eager loaded
        $consultations = Consultation::with([
            'patient.user',       // for patient name
            'patientMember',      // for patientMember name
            'doctorProfile.user', // for doctor name
        ])->paginate($perPage);
        // Wrap the paginated consultations in resource collection
        $list = ConsultationListResource::collection($consultations);
        $apiResponse = [
            'analytics' => $analytics,
            'list' => $list,
            'pagination' => [
                'total' => $consultations->total(),
                'per_page' => $consultations->perPage(),
                'current_page' => $consultations->currentPage(),
                'last_page' => $consultations->lastPage(),
                'from' => $consultations->firstItem(),
                'to' => $consultations->lastItem()
            ]
        ];
        return $this->sendResponse($apiResponse, __('Doctor data List fetched successfully.'));
    }

    private function getDoctorAnalytics(): array
    {
        return [
            'allConsultations' => Consultation::count(),    // Use count() for performance, not all()
            'completeConsultation' => Consultation::where('consultation_status', 'completed')->count(),
            'cancelConsultation' => Consultation::where('consultation_status', 'cancel')->count(),
            'activeConsultation' => Consultation::where('consultation_status', 'pending')->count(),
            'home_consultation' => Consultation::where('consultation_type', 'home')->count(),
            'chat_consultation' => Consultation::where('consultation_type', 'chat')->count(),
            'activeDoctors' => 0, // You can update this logic if you want to count active doctors
        ];
    }
    public function consultationDetails($id): \Illuminate\Http\JsonResponse
    {
        // Fetch the consultation with necessary relationships
        $consultation = Consultation::with([
            'patient.user',       // Patient's user info
            'patientMember',      // Patient member info
            'doctorProfile.user', // Doctor info
        ])->find($id);

        // Check if consultation exists
        if (!$consultation) {
            return $this->sendError( __('Doctor data not found.'));
        }
        $apiResponse = new ConsultationDetailResource($consultation);
        // Return formatted resource
        return $this->sendResponse($apiResponse, __('Doctor data Detail fetched successfully.'));
    }

}
