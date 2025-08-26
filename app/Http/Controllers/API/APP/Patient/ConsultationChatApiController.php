<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\APP\Chatting\ChatMessageResource;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Message;
use App\Models\Patient;
use App\Models\PatientMember;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultationChatApiController extends Controller
{
    use ApiResponse;


    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'message'         => 'nullable|string|required_without:file|max:2000',
            'file'            => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Load consultation with relations
            $consultation = Consultation::with([
                'patient.user',
                'doctorProfile.user',
                'patientMember.patient'
            ])->findOrFail($request->consultation_id);

            // Check consultation status
            if (!in_array($consultation->consultation_status, ['pending', 'monitoring'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation is not active.'
                ], 403);
            }

            // Determine main patient and patient member
            $mainPatient = $consultation->patient ?? $consultation->patientMember?->patient;
            $patientMemberId = $consultation->patientMember?->id;

            if (!$mainPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Main patient not found.'
                ], 422);
            }

            $patientId = $mainPatient->id;

            // Validate patient member sender
            if ($user->user_type === 'patient_member') {
                $senderMember = PatientMember::where('user_id', $user->id)->first();
                if (!$senderMember || $senderMember->patient_id !== $mainPatient->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized patient member.'
                    ], 403);
                }
                $patientMemberId = $senderMember->id;
            }

            // Determine receiver
            if ($user->user_type === 'doctor') {
                $receiverId = $mainPatient->user_id;
            } else {
                $receiverId = $consultation->doctorProfile?->user_id;
            }

            if (!$receiverId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receiver not found.'
                ], 422);
            }

            // Handle file upload
            $filePath = $request->hasFile('file') ? $request->file('file')->store('messages', 'public') : null;

            // Create message
            $message = Message::create([
                'consultation_id'    => $consultation->id,
                'sender_id'          => $user->id,
                'receiver_id'        => $receiverId,
                'patient_id'         => $patientId,
                'patient_member_id'  => $patientMemberId,
                'content'            => $request->message,
                'file'               => $filePath,
            ]);

            DB::commit();
            //broadcast real time
            event(new MessageSent($message));

            return $this->sendResponse ( new ChatMessageResource($message),__('Message sent successfully.') );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SendMessage Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while sending the message.'
            ], 500);
        }
    }

    public function getMessageHistory(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
        ]);

        try {
            // Load consultation with related patient, doctor, and patient member
            $consultation = Consultation::with(['patient.user', 'doctorProfile.user', 'patientMember.patient'])
                ->findOrFail($request->consultation_id);

            // Check if the user has access to this consultation
            if ($user->user_type === 'patient' && $consultation->patient_id !== $user->patient->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }

            if ($user->user_type === 'patient_member') {
                $member = PatientMember::where('user_id', $user->id)->first();
                if (!$member || $consultation->patient_id !== $member->patient_id) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
                }
            }

            if ($user->user_type === 'doctor' && $consultation->doctorProfile->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }

            // Fetch messages for this consultation
            $messages = Message::where('consultation_id', $consultation->id)
                ->with(['sender', 'receiver', 'patient', 'patientMember'])
                ->orderBy('created_at', 'asc') // chronological order
                ->get();

            return response()->json([
                'success' => true,
                'data'    => ChatMessageResource::collection($messages),
            ]);

        } catch (\Throwable $e) {
            Log::error('GetMessageHistory Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Something went wrong while fetching messages.'], 500);
        }
    }


}
