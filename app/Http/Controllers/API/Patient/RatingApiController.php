<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Resources\RatingResource;
use App\Models\Rating;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingApiController extends Controller
{
    use ApiResponse;
    public function store(StoreRatingRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->only([
            'doctor_profile_id',
            'rating',
            'review',
            'patient_id',
            'patient_member_id',
        ]);

        // Check if rating exists for this patient or patient_member & doctor
        $query = Rating::where('doctor_profile_id', $data['doctor_profile_id']);

        if (!empty($data['patient_id'])) {
            $query->where('patient_id', $data['patient_id']);
        } elseif (!empty($data['patient_member_id'])) {
            $query->where('patient_member_id', $data['patient_member_id']);
        }

        $rating = $query->first();

        if ($rating) {
            // Update existing rating
            $rating->update($data);
            $message = 'Rating updated successfully.';
        } else {
            // Create new rating
            $rating = Rating::create($data);
            $message = 'Rating created successfully.';
        }

        return $this->sendResponse(new RatingResource($rating), __('Rating created successfully.'));
    }
}
