<?php

namespace App\Http\Controllers\API\Patient;

use App\Events\MessageSent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\Consultation;
use App\Models\Message;
use App\Models\PatientMember;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsultationChatApiController extends Controller
{
    use ApiResponse;
    //store message
//    public function sendMessage(SendMessageRequest $request): \Illuminate\Http\JsonResponse
//    {
//
//        $filePath = null;
//        $fileType = null;
//
//        if ($request->hasFile('file')) {
//            $file = $request->file('file');
//            $filePath = Helper::fileUpload($file, 'chat_files');
//        }
//
//        // Patient cannot message patient_member or vice versa
////        if ((
////            ($request->sender_type === 'patient' && $request->receiver_type === 'patient_member') ||
////            ($request->sender_type === 'patient_member' && $request->receiver_type === 'patient')
////        )) {
////            return response()->json(['error' => 'Patient and Patient Member cannot chat with each other.'], 403);
////        }
//
//        $messageData = [
//            'message' => $request->message,
//            'file' => $filePath,
//            'is_read' => false,
//        ];
//
//        $messageData['sender_' . $request->sender_type . '_id'] = $request->sender_id;
//        $messageData['receiver_' . $request->receiver_type . '_id'] = $request->receiver_id;
//
//        $chat = Message::create($messageData);
//
//        broadcast(new MessageSent($chat));
//
//        return $this->sendResponse(new ChatMessageResource($chat), __('Message sent successfully'));
//    }
    public function sendMessage(SendMessageRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validTypes = ['doctor_profile', 'patient', 'patient_member'];

            // ভ্যালিড স্যান্ডার ও রিসিভার চেক
            if (!in_array($request->sender_type, $validTypes) || !in_array($request->receiver_type, $validTypes)) {
                return response()->json(['error' => 'Invalid sender or receiver type'], 400);
            }

            // ===== Patient ↔ Patient Member  =====
            if (
                ($request->sender_type === 'patient' && $request->receiver_type === 'patient_member') ||
                ($request->sender_type === 'patient_member' && $request->receiver_type === 'patient')
            ) {
                if ($request->sender_type === 'patient') {
                    // শুধু নিজের মেম্বারকে চ্যাট করতে পারবে
                    $member = PatientMember::where('id', $request->receiver_id)
                        ->where('patient_id', $request->sender_id)
                        ->first();
                    if (!$member) {
                        return response()->json(['error' => 'You can only chat with your own patient members'], 403);
                    }
                } else {
                    // Patient member শুধু parent patient এর সাথে চ্যাট করতে পারবে
                    $member = PatientMember::where('id', $request->sender_id)
                        ->where('patient_id', $request->receiver_id)
                        ->first();
                    if (!$member) {
                        return response()->json(['error' => 'Patient members can only chat with their parent patient'], 403);
                    }
                }
            }
            // ===== Doctor ↔ Patient Member চ্যাট নিষিদ্ধ =====
            if (
                ($request->sender_type === 'patient_member' && $request->receiver_type === 'doctor_profile') ||
                ($request->sender_type === 'doctor_profile' && $request->receiver_type === 'patient_member')
            ) {
                return response()->json(['error' => 'Doctors cannot chat with patient members'], 403);
            }

            // ===== Patient ↔ Doctor চ্যাট চেক (Consultation অনুযায়ী) =====
            if ($request->sender_type === 'patient' && $request->receiver_type === 'doctor_profile') {
                $consultationExists = Consultation::where('patient_id', $request->sender_id)
                    ->where('doctor_profile_id', $request->receiver_id)
                    ->exists();
                if (!$consultationExists) {
                    return response()->json(['error' => 'You can only message doctors you have consulted with'], 403);
                }
            }

            if ($request->sender_type === 'doctor_profile' && $request->receiver_type === 'patient') {
                $consultationExists = Consultation::where('doctor_profile_id', $request->sender_id)
                    ->where('patient_id', $request->receiver_id)
                    ->exists();
                if (!$consultationExists) {
                    return response()->json(['error' => 'You can only message patients who have consulted with you'], 403);
                }
            }

            // ===== File Upload =====
            $filePath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $request->validate([
                    'file' => 'mimes:jpg,png,pdf,doc,docx|max:10240',
                ]);
                $filePath = Helper::fileUpload($file, 'chat_files');
            }

            // ===== Message Save =====
            $messageData = [
                'message' => $request->message,
                'file' => $filePath,
                'is_read' => false,
                'sender_' . $request->sender_type . '_id' => $request->sender_id,
                'receiver_' . $request->receiver_type . '_id' => $request->receiver_id,
            ];

            $chat = Message::create($messageData);

            return $this->sendResponse(new ChatMessageResource($chat), __('Message sent successfully'));

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to send message', 'details' => $e->getMessage()], 500);
        }
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
