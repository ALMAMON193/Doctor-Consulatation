<?php

namespace App\Http\Controllers\API\Patient;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatParticipantResource;
use App\Models\Consultation;
use App\Models\Message;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsultationChatApiController extends Controller
{
    use ApiResponse;

    public function getChatParticipantsInfo(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user()->load(['doctorProfile', 'patient.patientMembers', 'patient.user']);

        $response = new ChatParticipantResource([
            'doctorProfile'   => $this->getPaidDoctors($user),
            'patientProfile'  => $user->patient ?? [],
        ]);

        return $this->sendResponse($response, __('Information retrieved successfully.'));
    }

    private function getPaidDoctors($user)
    {
        if (!$user->patient) return collect();

        $patient = $user->patient;
        $memberIds = $patient->patientMembers->pluck('id')->toArray();
        $membersMap = $patient->patientMembers->keyBy('id');

        $consultations = \App\Models\Consultation::with('doctorProfile')
            ->where('payment_status', 'paid')
            ->where(function ($q) use ($patient, $memberIds) {
                $q->where('patient_id', $patient->id)
                    ->orWhereIn('patient_member_id', $memberIds);
            })
            ->orderByDesc('id')
            ->get();

        return $consultations
            ->filter(fn($c) => $c->doctorProfile !== null)
            ->groupBy('doctor_profile_id')
            ->map(function ($group) use ($user, $membersMap) {
                $consult = $group->first();
                $doctor = $consult->doctorProfile;

                // Attach paid_by info
                if ($consult->patient_id) {
                    $doctor->paid_by = [
                        'id'                    => $user->id,
                        'name'                  => $user->name,
                        'type'                  => 'patient',
                        'profile_picture'       => $user->profile_picture ?? ""
                    ];
                } elseif ($consult->patient_member_id && isset($membersMap[$consult->patient_member_id])) {
                    $member = $membersMap[$consult->patient_member_id];
                    $doctor->paid_by = [
                        'id'                    => $member->id,
                        'name'                  => $member->name,
                        'type'                  => 'patient_member',
                        'profile_picture'       => $user->profile_picture ?? ""
                    ];
                }
                return $doctor;
            })->values();
    }


    //store message
    public function sendMessage(SendMessageRequest $request): \Illuminate\Http\JsonResponse
    {
        $filePath = null;
        $fileType = null;

        // Handle optional file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = Helper::fileUpload($file, 'chat_files');
            $fileType = $file->getClientMimeType();
        }

        // Prepare message data
        $messageData = [
            'message'   => $request->message,
            'file'      => $filePath,
            'file_type' => $fileType,
            'is_read'   => false,
        ];

        // Dynamically assign sender ID
        $messageData['sender_' . $request->sender_type . '_id'] = $request->sender_id;

        // Dynamically assign receiver ID
        $messageData['receiver_' . $request->receiver_type . '_id'] = $request->receiver_id;

        // Store message
        $chat = Message::create($messageData);

        return $this->sendResponse(
            new ChatMessageResource($chat),
            __('Message sent successfully.')
        );
    }


}
