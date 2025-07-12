<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'sender' => [
                'type' => $this->getSenderType(),
                'id' => intval($this->getSenderId()),
                'name' => $this->getSenderName(),
                'photo' => $this->getSenderPhoto() ?? '',
            ],
            'receiver' => [
                'type' => $this->getReceiverType(),
                'id' => intval($this->getReceiverId()),
                'name' => $this->getReceiverName(),
                'photo' => $this->getReceiverPhoto() ?? '',
            ],
            'file_url' => $this->file ? asset($this->file) : null,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    protected function getSenderType(): ?string
    {
        if ($this->sender_doctor_profile_id) return 'doctor_profile';
        if ($this->sender_patient_id) return 'patient';
        if ($this->sender_patient_member_id) return 'patient_member';
        return null;
    }

    protected function getSenderId() {
        return $this->sender_doctor_profile_id ?? $this->sender_patient_id ?? $this->sender_patient_member_id;
    }

    protected function getReceiverType(): ?string
    {
        if ($this->receiver_doctor_profile_id) return 'doctor_profile';
        if ($this->receiver_patient_id) return 'patient';
        if ($this->receiver_patient_member_id) return 'patient_member';
        return null;
    }

    protected function getReceiverId() {
        return $this->receiver_doctor_profile_id ?? $this->receiver_patient_id ?? $this->receiver_patient_member_id;
    }

    protected function getSenderName() {
        return $this->senderDoctorProfile?->personal_name
            ?? $this->senderPatient?->user?->name
            ?? $this->senderPatientMember?->name;
    }

    protected function getSenderPhoto() {
        return $this->senderDoctorProfile?->profile_picture
            ?? $this->senderPatient?->profile_photo
            ?? $this->senderPatientMember?->profile_photo;
    }

    protected function getReceiverName() {
        return $this->receiverDoctorProfile?->personal_name
            ?? $this->receiverPatient?->user?->name
            ?? $this->receiverPatientMember?->name;
    }

    protected function getReceiverPhoto(): ?string
    {
        $photo = $this->receiverDoctorProfile?->profile_picture
            ?? $this->receiverPatient?->profile_photo
            ?? $this->receiverPatientMember?->profile_photo;

        return $photo ? asset($photo) : null;
    }
}
