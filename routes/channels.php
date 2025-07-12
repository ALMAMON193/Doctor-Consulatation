<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.doctor.{doctorId}.patient.{patientId}', function ($user, $doctorId, $patientId) {
    return ($user->doctorProfile && $user->doctorProfile->id == $doctorId)
        || ($user->patient && $user->patient->id == $patientId);
});

Broadcast::channel('chat.doctor.{doctorId}.member.{memberId}', function ($user, $doctorId, $memberId) {
    return ($user->doctorProfile && $user->doctorProfile->id == $doctorId)
        || ($user->patientMember && $user->patientMember->id == $memberId);
});

