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
    public function getNotifiableUserAttribute()
    {
        if ($this->patient_id && $this->patient && $this->patient->user) {
            // Direct patient consultation - notify the patient's user
            return $this->patient->user;
        } elseif ($this->patient_member_id &&
            $this->patientMember &&
            $this->patientMember->patient &&
            $this->patientMember->patient->user) {
            // Patient member consultation - notify the parent patient's user
            return $this->patientMember->patient->user;
        }

        return null;
    }

// Updated accept function in your controller
    public function accept(Consultation $consultation)
    {
        try {
            DB::transaction(function () use (&$consultation) {

                // Add debugging information
                Log::info("Consultation Debug Info:", [
                    'consultation_id' => $consultation->id,
                    'patient_id' => $consultation->patient_id,
                    'patient_member_id' => $consultation->patient_member_id,
                    'doctor_id' => $consultation->doctor_id,
                ]);

                // Load relations safely with nested relationships
                $consultation->load([
                    'patient.user',
                    'patientMember.patient.user',
                    'specialization',
                    'doctorProfile.user'
                ]);

                // Debug loaded relationships
                Log::info("Loaded Relations Debug:", [
                    'has_patient' => $consultation->patient ? 'yes' : 'no',
                    'has_patient_user' => $consultation->patient?->user ? 'yes' : 'no',
                    'has_patient_member' => $consultation->patientMember ? 'yes' : 'no',
                    'has_patient_member_patient' => $consultation->patientMember?->patient ? 'yes' : 'no',
                    'has_patient_member_patient_user' => $consultation->patientMember?->patient?->user ? 'yes' : 'no',
                ]);

                // Already assigned check
                if ($consultation->doctor_id) {
                    throw new \Exception('This consultation has already been assigned.');
                }

                // Authenticated doctor
                $doctor = auth()->user()->doctorProfile
                    ?? throw new \Exception('Authenticated user has no doctor profile.');

                // Check payment completed
                $hasPaid = $consultation->payment()->where('status', 'completed')->exists();
                if (!$hasPaid) {
                    throw new \Exception('Payment not completed for this consultation. Cannot accept.');
                }

                // Assign consultation
                $consultation->doctor_id = $doctor->id;
                $consultation->consultation_status = 'monitoring';
                $consultation->assign_application = $doctor->name ?? $doctor->user?->name ?? 'Unknown';
                $consultation->assign_at = now();
                $consultation->save();

                // Determine who to notify based on consultation type
                $notifiableUser = null;

                Log::info("Starting notification logic for consultation: {$consultation->id}");

                if ($consultation->patient_id) {
                    Log::info("Consultation has patient_id: {$consultation->patient_id}");

                    if ($consultation->patient) {
                        Log::info("Patient relationship exists");

                        if ($consultation->patient->user) {
                            Log::info("Patient user relationship exists");
                            $notifiableUser = $consultation->patient->user;
                        } else {
                            Log::warning("Patient user is null for patient_id: {$consultation->patient_id}");
                        }
                    } else {
                        Log::warning("Patient relationship is null for patient_id: {$consultation->patient_id}");
                    }

                } elseif ($consultation->patient_member_id) {
                    Log::info("Consultation has patient_member_id: {$consultation->patient_member_id}");

                    if ($consultation->patientMember) {
                        Log::info("PatientMember relationship exists");

                        if ($consultation->patientMember->patient) {
                            Log::info("PatientMember's patient relationship exists");

                            if ($consultation->patientMember->patient->user) {
                                Log::info("PatientMember's patient user relationship exists");
                                $notifiableUser = $consultation->patientMember->patient->user;
                            } else {
                                Log::warning("PatientMember's patient user is null");
                            }
                        } else {
                            Log::warning("PatientMember's patient is null for patient_member_id: {$consultation->patient_member_id}");
                        }
                    } else {
                        Log::warning("PatientMember relationship is null for patient_member_id: {$consultation->patient_member_id}");
                    }
                } else {
                    Log::warning("Consultation has neither patient_id nor patient_member_id");
                }

                // Send notification
                if ($notifiableUser) {
                    Log::info("Sending notification to user: {$notifiableUser->id}");
                    try {
                        // Make sure we have all relationships loaded for notification
                        $consultation->load(['doctorProfile.user', 'specialization']);

                        $notifiableUser->notify(
                            new \App\Notifications\ConsultationAssignedNotification($consultation)
                        );
                        Log::info("Notification sent successfully to user: {$notifiableUser->id}");
                    } catch (\Exception $e) {
                        Log::error("Notification failed: " . $e->getMessage());
                        Log::error("Notification error trace: " . $e->getTraceAsString());
                    }
                } else {
                    Log::warning("No user found to notify for consultation ID: {$consultation->id}");
                }
            }); // transaction end

            // Refresh to get latest data
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
