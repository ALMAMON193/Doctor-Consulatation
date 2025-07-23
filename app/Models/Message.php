<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $receiver_patient_member_id
 * @property mixed $sender_doctor_profile_id
 * @property mixed $receiver_patient_id
 */
class Message extends Model
{
    protected $fillable = [
        'sender_doctor_profile_id',
        'sender_patient_id',
        'sender_patient_member_id',
        'receiver_doctor_profile_id',
        'receiver_patient_id',
        'receiver_patient_member_id',
        'file',
        'file_type',
        'message',
        'is_read',
    ];

    public function senderDoctorProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'sender_doctor_profile_id');
    }

    public function senderPatient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class, 'sender_patient_id');
    }

    public function senderPatientMember(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PatientMember::class, 'sender_patient_member_id');
    }

    public function receiverDoctorProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'receiver_doctor_profile_id');
    }

    public function receiverPatient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class, 'receiver_patient_id');
    }

    public function receiverPatientMember(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PatientMember::class, 'receiver_patient_member_id');
    }
}
