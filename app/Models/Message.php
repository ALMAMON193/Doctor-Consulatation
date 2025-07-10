<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $data)
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
        'message',
        'is_read',
    ];

    // Sender
    public function senderDoctorProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    { return $this->belongsTo(DoctorProfile::class, 'sender_doctor_profile_id');
    }
    public function senderPatient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    { return $this->belongsTo(Patient::class, 'sender_patient_id');
    }
    public function senderPatientMember(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    { return $this->belongsTo(PatientMember::class, 'sender_patient_member_id');
    }

// Receiver
    public function receiverDoctorProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    { return $this->belongsTo(DoctorProfile::class, 'receiver_doctor_profile_id');
    }
    public function receiverPatient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    { return $this->belongsTo(Patient::class, 'receiver_patient_id');
    }
    public function receiverPatientMember(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    { return $this->belongsTo(PatientMember::class, 'receiver_patient_member_id');
    }
}
