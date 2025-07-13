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
    public function sendMessage(SendMessageRequest $request): \Illuminate\Http\JsonResponse
    {
        $filePath = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = Helper::fileUpload($file, 'chat_files');
        }

        // Patient cannot message patient_member or vice versa
        if ((
            ($request->sender_type === 'patient' && $request->receiver_type === 'patient_member') ||
            ($request->sender_type === 'patient_member' && $request->receiver_type === 'patient')
        )) {
            return response()->json(['error' => 'Patient and Patient Member cannot chat with each other.'], 403);
        }

        $messageData = [
            'message' => $request->message,
            'file' => $filePath,
            'is_read' => false,
        ];

        $messageData['sender_' . $request->sender_type . '_id'] = $request->sender_id;
        $messageData['receiver_' . $request->receiver_type . '_id'] = $request->receiver_id;

        $chat = Message::create($messageData);

        broadcast(new MessageSent($chat));

        return $this->sendResponse(new ChatMessageResource($chat), __('Message sent successfully'));
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
