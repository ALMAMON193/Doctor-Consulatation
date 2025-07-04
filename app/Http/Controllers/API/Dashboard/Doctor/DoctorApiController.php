<?php

namespace App\Http\Controllers\API\Dashboard\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorListResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DoctorApiController extends Controller
{
    use ApiResponse;
    public function doctorList(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $doctors = User::with('doctorProfile')
            ->where('user_type', 'doctor')
            ->paginate($perPage);

        $resourceCollection = DoctorListResource::collection($doctors);

        // Manually pass original paginator to sendResponse()
        return $this->sendResponse($doctors->setCollection(collect($resourceCollection->resolve())), __('Doctor list successfully.'));

    }
    //doctor Create for admin dashboard
    public function doctorDetails($id): \Illuminate\Http\JsonResponse
    {
        $doctor = User::with(['doctorProfile', 'address', 'personalDetails'])
            ->where('id', $id)
            ->where('user_type', 'doctor')
            ->first();

        if (!$doctor) {
            return $this->sendResponse(null, 'Doctor not found', null, 404);
        }

        $data = [
            'name'         => $doctor->name,
            'email'        => $doctor->email,
            'phone'        => $doctor->phone_number,
            'specialty'    => optional($doctor->doctorProfile)->specialization,
            'consulted'    => optional($doctor->doctorProfile)->consulted,
            'subscription' => optional($doctor->doctorProfile)->subscription,
            'status'       => optional($doctor->doctorProfile)->verification_status,
            'address'      => optional($doctor->userAddress),
            'personal'     => optional($doctor->userPersonalDetail),
        ];

        return $this->sendResponse($data, 'Doctor details fetched successfully');
    }


}
