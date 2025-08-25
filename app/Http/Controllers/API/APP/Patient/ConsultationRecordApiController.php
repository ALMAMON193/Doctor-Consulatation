<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\APP\Patient\ConsultationResource;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\PatientMember;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ConsultationRecordApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->sendResponse([], 'User not found');
        }

        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->sendResponse([], 'Patient record not found');
        }

        $memberIds = PatientMember::where('patient_id', $patient->id)->pluck('id')->toArray();

        $consultations = Consultation::with(['doctorProfile.user'])
            ->where(function ($q) use ($patient, $memberIds) {
                $q->where('patient_id', $patient->id)
                    ->orWhereIn('patient_member_id', $memberIds);
            })
            ->orderByDesc('created_at')
            ->where('payment_status','paid')
            ->get();

        $ongoing = ConsultationResource::collection(
            $consultations->where('consultation_status', 'monitoring')->values()
        );
        $closed = ConsultationResource::collection(
            $consultations->where('consultation_status', 'completed')->values()
        );

        return $this->sendResponse([
            'ongoing' => $ongoing,
            'closed' => $closed,
        ], __('Consultation records retrieved successfully'));
    }
    //delete close consultation record
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->sendResponse([], 'User not found', 401);
        }

        $patient = \App\Models\Patient::where('user_id', $user->id)->first();
        $memberIds = \App\Models\PatientMember::where('patient_id', optional($patient)->id)->pluck('id')->toArray();

        // Find the consultation by ID and ownership
        $consultation = Consultation::where(function ($query) use ($patient, $memberIds) {
            $query->where('patient_id', optional($patient)->id)
                ->orWhereIn('patient_member_id', $memberIds);
        })
            ->where('id', $id)
            ->first();

        // Check if consultation is completed
        if ($consultation->consultation_status !== 'completed') {
            return $this->sendError(
                __('Your consultation is still in monitoring. Only completed consultations can be deleted.'),
            );
        }

        $consultation->delete();
        return $this->sendResponse([], 'Consultation deleted successfully');
    }


}
