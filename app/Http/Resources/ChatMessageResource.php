<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'message' => $this->message ?? '',
            'is_read' => $this->is_read,
            'sender' => [
                'type' => $this->getSenderType() ?? 'unknown',
                'id' => intval($this->getSenderId() ?? 0),
                'name' => $this->getSenderName() ?? 'Unknown Sender',
                'photo' => $this->getSenderPhoto() ?? '',
            ],
            'receiver' => [
                'type' => $this->getReceiverType() ?? 'unknown',
                'id' => intval($this->getReceiverId() ?? 0),
                'name' => $this->getReceiverName() ?? 'Unknown Receiver',
                'photo' => $this->getReceiverPhoto() ?? '',
            ],
            'file_url' => $this->file ? asset($this->file) : null,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
        Log::info('ChatMessageResource transformed', ['message_id' => $this->id, 'data' => $data]);
        return $data;
    }

    protected function getSenderType(): ?string
    {
        $type = $this->sender_doctor_profile_id ? 'doctor_profile' :
            ($this->sender_patient_id ? 'patient' :
                ($this->sender_patient_member_id ? 'patient_member' : null));
        Log::info('Sender type determined', ['message_id' => $this->id, 'sender_type' => $type]);
        return $type;
    }

    protected function getSenderId()
    {
        $id = $this->sender_doctor_profile_id ?? $this->sender_patient_id ?? $this->sender_patient_member_id;
        Log::info('Sender ID determined', ['message_id' => $this->id, 'sender_id' => $id]);
        return $id;
    }

    protected function getReceiverType(): ?string
    {
        $type = $this->receiver_doctor_profile_id ? 'doctor_profile' :
            ($this->receiver_patient_id ? 'patient' :
                ($this->receiver_patient_member_id ? 'patient_member' : null));
        Log::info('Receiver type determined', ['message_id' => $this->id, 'receiver_type' => $type]);
        return $type;
    }

    protected function getReceiverId()
    {
        $id = $this->receiver_doctor_profile_id ?? $this->receiver_patient_id ?? $this->receiver_patient_member_id;
        Log::info('Receiver ID determined', ['message_id' => $this->id, 'receiver_id' => $id]);
        return $id;
    }

    protected function getSenderName(): ?string
    {
        $name = $this->senderDoctorProfile?->personal_name
            ?? $this->senderPatient?->user?->name
            ?? $this->senderPatientMember?->name
            ?? null;
        Log::info('Sender name determined', ['message_id' => $this->id, 'sender_name' => $name]);
        return $name;
    }

    protected function getSenderPhoto(): ?string
    {
        $photo = $this->senderDoctorProfile?->profile_picture
            ?? $this->senderPatient?->profile_photo
            ?? $this->senderPatientMember?->profile_photo;
        $photo = $photo ? asset($photo) : null;
        Log::info('Sender photo determined', ['message_id' => $this->id, 'sender_photo' => $photo]);
        return $photo;
    }

    protected function getReceiverName(): ?string
    {
        $name = $this->receiverDoctorProfile?->personal_name
            ?? $this->receiverPatient?->user?->name
            ?? $this->receiverPatientMember?->name
            ?? null;
        Log::info('Receiver name determined', ['message_id' => $this->id, 'receiver_name' => $name]);
        return $name;
    }

    protected function getReceiverPhoto(): ?string
    {
        $photo = $this->receiverDoctorProfile?->profile_picture
            ?? $this->receiverPatient?->profile_photo
            ?? $this->receiverPatientMember?->profile_photo;
        $photo = $photo ? asset($photo) : null;
        Log::info('Receiver photo determined', ['message_id' => $this->id, 'receiver_photo' => $photo]);
        return $photo;
    }
}
