<?php

namespace App\Http\Controllers\API\WEB\Dashboard\Coupon;

use App\Http\Controllers\Controller;
use App\Http\Resources\WEB\Dashboard\Coupon\CouponDetailsResource;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // Pagination
        $perPage = $request->input('per_page', 10);
        $coupons = Coupon::paginate($perPage);

        // Analytics
        $analytics = [
            'total_coupons' => Coupon::count(),
            'active_coupons' => Coupon::where('status', 'active')->count(),
            'used_coupons' => Coupon::where('status', 'used')->count(),
            'expired_coupons' => Coupon::where('status', 'expired')->count(),
            'total_usage_count' => Coupon::sum('used_count'), // total uses across all coupons
        ];

        // Prepare response data
        $coupons = [
            'analytics' => $analytics,
            'coupons' => \App\Http\Resources\WEB\Dashboard\Coupon\ListCouponResource::collection($coupons),
            'pagination' => [
                'total' => $coupons->total(),
                'per_page' => $coupons->perPage(),
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'from' => $coupons->firstItem(),
                'to' => $coupons->lastItem(),
            ],
        ];
        return $this->sendResponse($coupons, __('Coupons retrieved successfully.'));
    }



    public function store (\App\Http\Requests\WEB\Dashboard\Coupon\CouponStoreRequest $request)
    {
        // Create the coupon
        $coupon = Coupon::create ($request->validated ());

        // Return the newly created coupon in the API response
        return $this->sendResponse (
            new \App\Http\Resources\WEB\Dashboard\Coupon\CreateCouponResource($coupon),
            __ ('Coupon successfully created!')
        );
    }

    public function show(Coupon $coupon)
    {
        $coupon->load(['couponUsers.patient.user', 'couponUsers.patientMember.patient.user']);

        return $this->sendResponse(
            new CouponDetailsResource($coupon),
            __('Coupon details fetched successfully.')
        );
    }
}
