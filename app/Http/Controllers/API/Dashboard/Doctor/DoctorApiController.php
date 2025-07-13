<?php

namespace App\Http\Controllers\API\Dashboard\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Doctor\StoreRequest;
use App\Http\Resources\Dashboard\Doctor\DoctorDetailResource;
use App\Http\Resources\Dashboard\Doctor\DoctorListResource;
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
            ->latest()
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
    //create  doctor for admin
    public function createDoctor(StoreRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('User not authenticated.'), [], 401);
        }

        if ($user->user_type !== 'admin') {
            return $this->sendError(__('Only Admin can access this page'), [], 403);
        }

        $validated = $request->validated();
        $validated['user_type'] = 'doctor'; // ✅ Set user_type after validation
        $doctor = User::create($validated); // ✅ Only pass one argument

        return $this->sendResponse([], __('Doctor created successfully.'));
    }
}
