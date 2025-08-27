<?php

use App\Models\Consultation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('consultation.{consultationId}', function ($user, $consultationId) {
     $consultation = Consultation::find($consultationId);
     if (!$consultation) {
          Log::warning("Channel join failed: consultation {$consultationId} not found", ['user_id' => $user->id]);
          return false;
     }
     $patientUserId = optional($consultation->patient)->user_id
          ?: optional($consultation->patientMember?->patient)->user_id;
     $doctorUserId = optional($consultation->doctorProfile)->user_id;

     $allowed = in_array($user->id, array_filter([$patientUserId, $doctorUserId]));

     if ($allowed) {
          Log::info("User joined consultation channel", [
               'user_id' => $user->id,
               'consultation_id' => $consultationId
          ]);
     } else {
          Log::warning("Unauthorized join attempt", [
               'user_id' => $user->id,
               'consultation_id' => $consultationId
          ]);
     }

     return $allowed;
});
