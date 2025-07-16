<?php

namespace App\Http\Controllers\API\Dashboard\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Doctor\StoreRequest;
use App\Http\Resources\Dashboard\Doctor\DoctorDetailResource;
use App\Http\Resources\Dashboard\Doctor\DoctorListResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorApiController extends Controller
{
    use ApiResponse;

    public function doctorList(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        // ✅ Get analytics summary
        $analytics = $this->getDoctorAnalytics();

        // ✅ Get doctors list with profile
        $doctors = User::with('doctorProfile')
            ->where('user_type', 'doctor')
            ->latest()
            ->paginate($perPage);

        $responseApi = [
            'analytics' => $analytics,
            'doctor_list' => DoctorListResource::collection($doctors),
        ];
        return $this->sendResponse($responseApi, __('Doctors List'));
    }
    private function getDoctorAnalytics(): array
    {
        $allDoctors = User::where('user_type', 'doctor')->count();

        $doctorStatuses = DB::table('doctor_profiles')
            ->select('id', 'verification_status')
            ->get();

        $consultationCounts = DB::table('consultations')
            ->select('doctor_profile_id', DB::raw('COUNT(*) as total_consulted'))
            ->where('consultation_status', 'completed')
            ->groupBy('doctor_profile_id')
            ->pluck('total_consulted', 'doctor_profile_id');


        return [
            'allDoctors' => $allDoctors,
            'verifiedDoctor' => $doctorStatuses->where('verification_status', 'approved')->count(),
            'pendingDoctor' => $doctorStatuses->where('verification_status','pending')->count(),
            'unverifiedDoctor' => $doctorStatuses->where('verification_status','unverified')->count(),
        ];
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
