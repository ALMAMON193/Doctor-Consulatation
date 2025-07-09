<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreCouponRequest;
use App\Http\Requests\Doctor\UpdateCouponRequest;
use App\Http\Resources\DoctorCouponResource;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CouponApiController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('Doctor not authenticated.'), [], 401);
        }
        $doctorProfile = $user->doctorProfile;
        if (!$doctorProfile) {
            return $this->sendError(__('Doctor profile not found for this user.'), [], 404);
        }
        // Fetch active coupons using scopeActive
        $doctorCoupons = Coupon::active()
            ->where('doctor_profile_id', $doctorProfile->id)
            ->latest()->get();
        if ($doctorCoupons->isEmpty()) {
            return $this->sendResponse([], __('No active coupons found for this doctor.'));
        }

        return $this->sendResponse(
            DoctorCouponResource::collection($doctorCoupons),
            __('Doctor coupons fetched successfully.')
        );
    }

    public function store(StoreCouponRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('Doctor not authenticated.'), [], 401);
        }

        $doctorProfile = $user->doctorProfile;

        if (!$doctorProfile) {
            return $this->sendError(__('Doctor profile not found for this user.'), [], 404);
        }

        $data = $request->validated();
        $data['doctor_profile_id'] = $doctorProfile->id;
        $data['used_count'] = 0;

        $coupon = Coupon::create($data);

        return $this->sendResponse(
            new DoctorCouponResource($coupon),
            __('Doctor coupon created successfully.')
        );
    }
    public function update(UpdateCouponRequest $request, $id): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('Doctor not authenticated.'), [], 401);
        }

        $doctorProfile = $user->doctorProfile;

        if (!$doctorProfile) {
            return $this->sendError(__('Doctor profile not found.'), [], 404);
        }

        $coupon = Coupon::where('id', $id)
            ->where('doctor_profile_id', $doctorProfile->id)
            ->first();

        if (!$coupon) {
            return $this->sendError(__('Coupon not found or unauthorized.'), [], 404);
        }

        $data = $request->validated();
        $coupon->update($data);

        return $this->sendResponse(
            new DoctorCouponResource($coupon),
            __('Doctor coupon updated successfully.')
        );
    }


    //delete coupon
    public function destroy($id)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('Doctor not authenticated.'), [], 401);
        }
        $doctorProfile = $user->doctorProfile;
        if (!$doctorProfile) {
            return $this->sendError(__('Doctor profile not found.'), [], 404);
        }
        $coupon = Coupon::where('id', $id)
            ->where('doctor_profile_id', $doctorProfile->id)
            ->first();
        if (!$coupon) {
            return $this->sendError(__('Coupon not Found'), [], 404);
        }
        $coupon->delete();
        return $this->sendResponse([], __('Doctor coupon deleted successfully.'));
    }


}
