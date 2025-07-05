<?php

namespace App\Http\Controllers\API\Dashboard\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorDetailResource;
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
        $doctor = User::with(['doctorProfile', 'address', 'personalDetails'])->find($id);

        if (!$doctor || $doctor->user_type !== 'doctor') {
            return $this->sendResponse(null, 'Doctor not found', null, 404);
        }
        return $this->sendResponse(new DoctorDetailResource($doctor), 'Doctor details fetched successfully');
    }

}
