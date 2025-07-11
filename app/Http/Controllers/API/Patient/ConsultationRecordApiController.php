<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
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
            ->get();

        $ongoing = ConsultationResource::collection(
            $consultations->where('consultation_status', 'pending')->values()
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

        $consultation = Consultation::where('consultation_status', 'completed')
            ->where(function ($query) use ($user) {
                $patient = \App\Models\Patient::where('user_id', $user->id)->first();
                $memberIds = \App\Models\PatientMember::where('patient_id', optional($patient)->id)->pluck('id');

                $query->where('patient_id', optional($patient)->id)
                    ->orWhereIn('patient_member_id', $memberIds);
            })
            ->find($id);

        if (!$consultation) {
            return $this->sendError('Consultation not found', ['error' => 'Consultation not found']);
        }
        $consultation->delete();

        return $this->sendResponse([], 'Consultation deleted successfully');
    }
}
