<?php

namespace App\Http\Controllers\API\Patient;

use App\Events\MessageSent;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\Consultation;
use App\Models\Message;
use App\Traits\ApiResponse;
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
        Log::info('sendMessage request received', [
            'sender_type' => $request->sender_type,
            'sender_id' => $request->sender_id,
            'receiver_type' => $request->receiver_type,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'has_file' => $request->hasFile('file'),
        ]);

        try {
            // Validate sender and receiver types
            $validTypes = ['doctor_profile', 'patient', 'patient_member'];
            if (!in_array($request->sender_type, $validTypes) || !in_array($request->receiver_type, $validTypes)) {
                Log::warning('Invalid sender or receiver type', [
                    'sender_type' => $request->sender_type,
                    'receiver_type' => $request->receiver_type,
                ]);
                return response()->json(['error' => 'Invalid sender or receiver type'], 400);
            }

            // Prevent patient-to-patient_member messaging
            if (
                ($request->sender_type === 'patient' && $request->receiver_type === 'patient_member') ||
                ($request->sender_type === 'patient_member' && $request->receiver_type === 'patient')
            ) {
                Log::warning('Forbidden: Patient and patient member cannot chat', [
                    'sender_type' => $request->sender_type,
                    'receiver_type' => $request->receiver_type,
                ]);
                return response()->json(['error' => 'Patient and patient member cannot chat with each other'], 403);
            }

            // Handle file upload
            $filePath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $request->validate([
                    'file' => 'mimes:jpg,png,pdf,doc,docx|max:10240',
                ]);
                $filePath = Helper::fileUpload($file, 'chat_files');
                Log::info('File uploaded', ['file_path' => $filePath]);
            }

            // Prepare message data
            $messageData = [
                'message' => $request->message,
                'file' => $filePath,
                'is_read' => false,
                'sender_' . $request->sender_type . '_id' => $request->sender_id,
                'receiver_' . $request->receiver_type . '_id' => $request->receiver_id,
            ];
            Log::info('Message data prepared', $messageData);

            // Create and broadcast message
            $chat = Message::create($messageData);
            Log::info('Message created', ['message_id' => $chat->id]);

            broadcast(new MessageSent($chat))->toOthers();
            Log::info('Message broadcasted', ['message_id' => $chat->id]);

            return $this->sendResponse(new ChatMessageResource($chat), __('Message sent successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'sender_type' => $request->sender_type,
                'sender_id' => $request->sender_id,
                'receiver_type' => $request->receiver_type,
                'receiver_id' => $request->receiver_id,
            ]);
            return response()->json(['error' => 'Failed to send message'], 500);
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
