<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\APP\Chatting\ChatMessageResource;
use App\Models\Consultation;
use App\Models\Message;
use App\Models\PatientMember;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultationChatApiController extends Controller
{
    use ApiResponse;

    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required_without:file|string|nullable',
            'file' => 'nullable|file',
            'patient_member_id' => 'nullable|exists:patient_members,id',
        ]);

        $consultation = Consultation::findOrFail($request->consultation_id);

        if (!in_array($consultation->consultation_status, ['pending', 'monitoring'])) {
            return response()->json(['error' => 'Consultation is not active.'], 403);
        }

        $receiver = User::findOrFail($request->receiver_id);

        // ডিফল্ট patient_id consultation থেকে আসবে
        $patientId = $consultation->patient_id;
        $patientMemberId = null;

        // যদি patient নিজের member দিয়ে message দেয়
        if ($request->filled('patient_member_id')) {
            $member = PatientMember::where('patient_id', $patientId)
                ->findOrFail($request->patient_member_id);
            $patientMemberId = $member->id;
        }

        // File upload
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('messages', 'public');
        }

        $message = Message::create([
            'consultation_id'   => $consultation->id,
            'sender_id'         => $user->id,
            'receiver_id'       => $receiver->id,
            'patient_id'        => $patientId,
            'patient_member_id' => $patientMemberId,
            'content'           => $request->message,
            'file'              => $filePath,
        ]);

        $responseApi = new ChatMessageResource($message);
        return $this->sendResponse($responseApi,__('Consultation message created successfully'));
    }


    public function getConversationHistory(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|integer', // only need user_id
        ]);

        $user = auth()->user();

        $receiver = \App\Models\User::findOrFail($request->receiver_id);

        $messages = \App\Models\Message::where(function ($q) use ($user, $receiver) {
            $q->where('sender_id', $user->id)
                ->where('receiver_id', $receiver->id);
        })
            ->orWhere(function ($q) use ($user, $receiver) {
                $q->where('sender_id', $receiver->id)
                    ->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->sendResponse(
            \App\Http\Resources\APP\Chatting\ChatMessageResource::collection($messages),
            __('Messages retrieved successfully')
        );
    }


}
