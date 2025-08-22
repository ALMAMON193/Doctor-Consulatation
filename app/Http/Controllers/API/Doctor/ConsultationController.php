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

    /**
     * View a consultation
     */
    public function show($id)
    {
        $consultation = Consultation::with([
            'patient.user',
            'patientMember.patient.user',
            'doctorProfile.user',
            'specialization',
            'payment'
        ])->find($id);

        if (!$consultation) {
            return $this->sendError('Consultation not found', [], 404);
        }

        $doctor = auth()->user()->doctorProfile;
        if (!$doctor) {
            return $this->sendError('Not authorized', [], 403);
        }

        if ($consultation->doctor_id && $consultation->doctor_id !== $doctor->id) {
            return $this->sendError('Consultation already assigned to another doctor', [], 403);
        }

        $response = [
            'id' => $consultation->id,
            'specialization' => $consultation->specialization_name,
            'complaint' => $consultation->complaint ?? '',
            'pain_level' => $consultation->pain_level ?? 0,
            'consultation_date' => $consultation->consultation_date?->toISOString(),
            'status' => $consultation->consultation_status ?? 'pending',
            'patient' => [
                'id' => $consultation->patient_id ?? $consultation->patient_member_id,
                'type' => $consultation->patient_id ? 'direct' : 'member',
                'name' => $consultation->patient_name,
            ],
            'doctor' => $consultation->doctor_name,
            'assign_at' => $consultation->assign_at?->toISOString(),
            'assign_application' => $consultation->assign_application,
            'payment_status' => $consultation->payment?->status ?? 'unpaid',
            'fee_amount' => $consultation->fee_amount ?? 0,
            'final_amount' => $consultation->final_amount ?? 0,
        ];
        return $this->sendResponse($response, 'Consultation details retrieved successfully');
    }
    /**
     * Accept a consultation
     */
    public function accept($id)
    {
        try {
            $consultation = Consultation::with(['payment', 'doctorProfile.user'])->find($id);

            if (!$consultation) {
                return $this->sendError('Consultation not found', [], 404);
            }

            DB::transaction(function () use ($consultation) {
                if ($consultation->doctor_id) {
                    throw new \Exception('This consultation has already been assigned.');
                }

                $doctor = auth()->user()->doctorProfile;
                if (!$doctor) {
                    throw new \Exception('Authenticated user has no doctor profile.');
                }

                if (!$consultation->is_paid) {
                    throw new \Exception('Payment not completed. Cannot accept consultation.');
                }

                $consultation->doctor_id = $doctor->id;
                $consultation->consultation_status = 'monitoring';
                $consultation->assign_application = $doctor->user?->name ?? 'Unknown Doctor';
                $consultation->assign_at = now();
                $consultation->save();

                // Notify patient
                if ($user = $consultation->notifiable_user) {
                    try {
                        $user->notify(
                            new \App\Notifications\ConsultationAssignedNotification($consultation)
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send notification', [
                            'consultation_id' => $consultation->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            $consultation->refresh()->load('doctorProfile.user');

            return $this->sendResponse([
                'consultation_id' => $consultation->id,
                'doctor_id' => $consultation->doctor_id,
                'doctor_name' => $consultation->doctor_name,
                'assign_at' => $consultation->assign_at?->toISOString(),
                'status' => $consultation->consultation_status,
            ], 'Consultation accepted successfully');

        } catch (\Exception $e) {
            Log::error('Consultation acceptance failed', [
                'consultation_id' => $consultation->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError($e->getMessage() ?? 'Something went wrong');
        }
    }
}
