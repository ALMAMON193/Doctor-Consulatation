<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\{Patient, DoctorProfile, PatientMember, Consultation};

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'       => 'nullable|string|max:1000',
            'file'          => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4,mov,avi|max:10240',

            'sender_type'   => ['required', Rule::in(['doctor_profile', 'patient', 'patient_member'])],
            'sender_id'     => ['required', 'integer', function ($attribute, $value, $fail) {
                $this->validateSenderExists($value, $fail);
            }],

            'receiver_type' => ['required', Rule::in(['doctor_profile', 'patient', 'patient_member'])],
            'receiver_id'   => ['required', 'integer', function ($attribute, $value, $fail) {
                $this->validateReceiverExists($value, $fail);
            }],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateConsultationIfNeeded($validator);
            $this->validateMessagePermission($validator);
        });
    }

    private function validateSenderExists($value, $fail): void
    {
        $exists = match ($this->sender_type) {
            'doctor_profile'   => DoctorProfile::find($value),
            'patient'          => Patient::find($value),
            'patient_member'   => PatientMember::find($value),
            default            => null,
        };

        if (!$exists) {
            $fail('Sender ID is invalid.');
        }
    }

    private function validateReceiverExists($value, $fail): void
    {
        $exists = match ($this->receiver_type) {
            'doctor_profile'   => DoctorProfile::find($value),
            'patient'          => Patient::find($value),
            'patient_member'   => PatientMember::find($value),
            default            => null,
        };

        if (!$exists) {
            $fail('Receiver ID is invalid.');
        }
    }

    private function validateMessagePermission($validator): void
    {
        $sender = $this->sender_type;
        $receiver = $this->receiver_type;

        // Patients and members can ONLY message doctor_profile
        if (in_array($sender, ['patient', 'patient_member']) && $receiver !== 'doctor_profile') {
            $validator->errors()->add('receiver_type', 'Patients and patient members can only message doctors.');
        }

        // Patients and patient_members cannot message each other or themselves
        if (
            ($sender === 'patient' && $receiver === 'patient_member') ||
            ($sender === 'patient_member' && $receiver === 'patient') ||
            ($sender === 'patient_member' && $receiver === 'patient_member')
        ) {
            $validator->errors()->add('receiver_type', 'Messaging between patient and patient member is not allowed.');
        }
    }

    private function validateConsultationIfNeeded($validator): void
    {
        // Only check if sender is patient/patient_member and receiver is doctor_profile
        if (!in_array($this->sender_type, ['patient', 'patient_member']) || $this->receiver_type !== 'doctor_profile') {
            return;
        }

        $query = Consultation::query()
            ->where('doctor_profile_id', $this->receiver_id)
            ->where('payment_status', 'paid');

        match ($this->sender_type) {
            'patient'        => $query->where('patient_id', $this->sender_id),
            'patient_member' => $query->where('patient_member_id', $this->sender_id),
        };

        if (!$query->exists()) {
            $validator->errors()->add('sender_id', 'This sender has no paid consultation with the doctor.');
        }
    }

    public function messages(): array
    {
        return [
            'sender_type.in'    => 'Sender type must be doctor_profile, patient, or patient_member.',
            'receiver_type.in'  => 'Receiver type must be doctor_profile, patient, or patient_member.',
            'file.mimes'        => 'File must be a valid format: jpg, jpeg, png, pdf, mp4, mov, avi.',
            'file.max'          => 'File size must not exceed 10MB.',
        ];
    }
}
