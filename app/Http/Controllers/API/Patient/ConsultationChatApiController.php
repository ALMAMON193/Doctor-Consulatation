<?php

namespace App\Http\Controllers\API\Patient;

use App\Events\MessageSent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;

use App\Http\Resources\ChatMessageResource;
use App\Models\Consultation;
use App\Models\DoctorProfile;
use App\Models\Message;
use App\Models\Patient;
use App\Models\PatientMember;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConsultationChatApiController extends Controller
{
    use ApiResponse;

    public function sendMessage(SendMessageRequest $request)
    {

        $user = Auth::user();
        // Fetch consultation
        $consultation = Consultation::findOrFail($request->consultation_id);

        // Check if consultation is active
        if (!in_array($consultation->consultation_status, ['pending', 'monitoring'])) {
            return response()->json(['error' => 'Consultation is not active.'], 403);
        }

        // Determine sender type
        $senderPatient = Patient::where('user_id', $user->id)->first();
        $senderDoctor = DoctorProfile::where('user_id', $user->id)->first();

        // Determine receiver type
        $receiver = User::findOrFail($request->receiver_id);
        $receiverPatient = Patient::where('user_id', $receiver->id)->first();
        $receiverDoctor = DoctorProfile::where('user_id', $receiver->id)->first();

        // Determine if sender is PatientMember (from consultation)
        $senderPatientMember = null;
        if ($consultation->patient_member_id) {
            $senderPatientMember = PatientMember::find($consultation->patient_member_id);
        }

        // Rule 1: Block Patient ↔ Patient Member direct chat
        if (($senderPatient && $senderPatientMember && $receiverPatient) ||
            ($senderPatientMember && $receiverPatient)
        ) {
            return response()->json(['error' => 'Direct messaging between patient and patient member is not allowed.'], 403);
        }

        // Rule 3: Doctor ↔ Patient must be via consultation
        if (($senderDoctor && !$receiverPatient) || ($receiverDoctor && !$senderPatient)) {
            return response()->json(['error' => 'Communication must be between a doctor and a patient.'], 403);
        }

        // Rule 4: Prevent Doctor ↔ Patient Member direct chat
        $patientId = $consultation->patient_id; // default patient id
        if (($senderDoctor && $senderPatientMember) || ($receiverDoctor && $senderPatientMember)) {
            // ✅ Allow Patient Member → Doctor, save under parent patient
            if ($receiverDoctor) {
                $patientId = $senderPatientMember->patient_id; // parent patient
            } else {
                return response()->json(['error' => 'Direct messaging between doctor and patient member is not allowed.'], 403);
            }
        }

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('messages', 'public');
        }

        // Create the message
        $message = Message::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'patient_id' => $patientId,      // parent patient if sender is member
            'receiver_id' => $receiver->id,
            'content' => $request->content,
            'file' => $filePath,
        ]);

        return new ChatMessageResource($message);
    }


    public function getConversationHistory(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'receiver_type' => 'required|in:doctor_profile,patient,patient_member',
            'receiver_id' => 'required|integer',
        ]);

        $user = auth()->user();

        // Determine sender type and id
        if ($user->doctorProfile) {
            $senderType = 'doctor_profile';
            $senderId = $user->doctorProfile->id;
        } elseif ($user->patient) {
            $senderType = 'patient';
            $senderId = $user->patient->id;
        } elseif ($user->patientMember) {
            $senderType = 'patient_member';
            $senderId = $user->patientMember->id;
        } else {
            return response()->json(['error' => 'Invalid user type.'], 403);
        }

        // Simple conversation query: messages between two parties regardless of sender/receiver sides
        $messages = \App\Models\Message::where(function ($q) use ($senderType, $senderId, $request) {
            $q->where("sender_{$senderType}_id", $senderId)
                ->where("receiver_{$request->receiver_type}_id", $request->receiver_id);
        })->orWhere(function ($q) use ($senderType, $senderId, $request) {
            $q->where("sender_{$request->receiver_type}_id", $request->receiver_id)
                ->where("receiver_{$senderType}_id", $senderId);
        })->orderBy('created_at', 'asc')->get();

        return $this->sendResponse(ChatMessageResource::collection($messages), __('Messages retrieved successfully'));
    }

}
