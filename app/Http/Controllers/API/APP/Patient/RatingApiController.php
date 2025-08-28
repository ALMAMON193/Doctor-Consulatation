<?php

namespace App\Http\Controllers\API\APP\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\APP\Patient\StoreRatingRequest;
use App\Http\Resources\APP\Patient\RatingResource;
use App\Models\Consultation;
use App\Models\PatientMember;
use App\Models\Rating;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class RatingApiController extends Controller
{
    use ApiResponse;
    public function store(StoreRatingRequest $request)
    {
        $authUser = Auth::user();
        $consultation = Consultation::findOrFail($request->consultation_id);
        if(!$consultation){
            return $this->sendResponse ([],__('Consultation does not exist'));
        }
        $data = [
            'rating' => $request->rating,
            'review' => $request->review,
            'given_by_id' => $authUser->id,
            'given_by_type' => $authUser->role,
        ];

        if ($authUser->role === 'patient') {
            $data['doctor_id'] = $consultation->doctor_id;
            $data['patient_id'] = $authUser->patient->id ?? null;
            $data['patient_member_id'] = $request->input('patient_member_id') ?? null;
        } elseif ($authUser->role === 'doctor') {
            $data['doctor_id'] = $authUser->doctorProfile->id ?? null;
            $data['patient_id'] = $consultation->patient_id;
            $data['patient_member_id'] = $consultation->patient_member_id;
        }

        // Check for existing rating (same user and target)
        $existing = Rating::where('given_by_id', $authUser->id)
            ->where('given_by_type', $authUser->role)
            ->where(function($q) use ($data) {
                if (!empty($data['doctor_id'])) $q->where('doctor_id', $data['doctor_id']);
                if (!empty($data['patient_id'])) $q->orWhere('patient_id', $data['patient_id']);
                if (!empty($data['patient_member_id'])) $q->orWhere('patient_member_id', $data['patient_member_id']);
            })->first();

        if ($existing) {
            $existing->update($data);
            $message = 'Rating updated successfully.';
            $rating = $existing;
        } else {
            $rating = Rating::create($data);
            $message = 'Rating created successfully.';
        }
        return $this->sendResponse ($message, $rating);
    }
}
