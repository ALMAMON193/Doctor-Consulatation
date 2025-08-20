<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    use ApiResponse;

    // ✅ view a consultation
    public function show (Consultation $consultation)
    {
        $consultation->load (['patient.user', 'patientMember.patient.user', 'doctorProfile.user', 'specialization']);

        $doctor = auth ()->user ()->doctorProfile;
        if (!$doctor) {
            return $this->errorResponse ('Not authorized', 403);
        }

        if ($consultation->doctorProfile && $consultation->doctorProfile->id !== $doctor->id) {
            return $this->errorResponse ('Consultation already assigned to another doctor', 403);
        }

        $apiResponse = [
            'id' => $consultation->id,
            'specialization' => $consultation->specialization?->name ?? 'Unknown',
            'complaint' => $consultation->complaint ?? '',
            'pain_level' => $consultation->pain_level ?? 0,
            'consultation_date' => $consultation->consultation_date,
            'status' => $consultation->consultation_status ?? 'pending',
            'patient' => [
                'name' => $consultation->patient->user?->name
                    ?? $consultation->patientMember?->name
                        ?? 'Unknown',
            ],
            'doctor' => $consultation->doctorProfile?->user?->name ?? null,
            'assign_at' => $consultation->assign_at,
            'assign_application' => $consultation->assign_application,
        ];

        return $this->sendResponse ($apiResponse, __ ('Consultation View Details'));
    }

    // ✅ accept consultation
    public function accept(Consultation $consultation)
    {
        try {
            DB::transaction(function () use (&$consultation) {

                $consultation->load(['patient.user', 'patientMember.patient.user']);

                if ($consultation->doctor_id) {
                    throw new \Exception('This consultation has already been assigned.');
                }

                $doctor = auth()->user()->doctorProfile
                    ?? throw new \Exception('Authenticated user has no doctor profile.');

                if (!\App\Models\Payment::where('consultation_id', $consultation->id)
                    ->where('status', 'completed')->exists()) {
                    throw new \Exception('Payment not completed for this consultation. Cannot accept.');
                }

                $consultation->doctor_id = $doctor->id;
                $consultation->consultation_status = 'monitoring';
                $consultation->assign_application = $doctor->name ?? $doctor->user?->name ?? 'Unknown';
                $consultation->assign_at = now();
                $consultation->save();

                $consultation->notifiable_user?->notify(
                    new \App\Notifications\ConsultationAssignedNotification($consultation)
                );
            });

            $consultation->refresh();

            return $this->sendResponse([
                'consultation_id' => $consultation->id,
                'doctor_id' => $consultation->doctor_id,
                'assign_at' => $consultation->assign_at,
                'status' => $consultation->consultation_status,
            ], __('Consultation Accepted'));

        } catch (\Exception $e) {
            Log::error('Consultation accept failed: ' . $e->getMessage());
            return $this->sendError($e->getMessage() ?? 'Something went wrong');
        }
    }



}
