<?php

namespace App\Http\Controllers\API\Dashboard\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Doctor\StoreRequest;
use App\Http\Resources\Dashboard\Doctor\DoctorDetailResource;
use App\Http\Resources\Dashboard\Doctor\DoctorListResource;
use App\Http\Resources\Dashboard\Patient\PatientListResource;
use App\Mail\DoctorAccountCreatedAdmin;
use App\Mail\PatientAccountCreatedAdmin;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DoctorApiController extends Controller
{
    use ApiResponse;
    public function doctorList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $statusFilter = $request->input('status'); // pending, verified, unverified, rejected
        //consultation count doctor assign in consultation
        $doctorsConsultation = User::where('user_type', 'doctor')
            ->withCount('assignedConsultations')
            ->get();
        // Example analytics, implement your own method
        $analytics = $this->getDoctorAnalytics();
        // Build query
        $query = User::where('user_type', 'doctor')
            ->withCount([
                'assignedConsultations', // total assigned consultations
                'assignedConsultations as completed_consultations_count' => function ($q) {
                    $q->where('consultation_status', 'completed'); // only completed
                }
            ])
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->whereHas('doctorProfile', function ($q2) use ($statusFilter) {
                    $q2->where('verification_status', $statusFilter);
                });
            });
        // Paginate
        $doctors = $query->paginate($perPage);

        // Format response using resource
        $collection = DoctorListResource::collection($doctors)->resolve();
        $doctors->setCollection(collect($collection));

        $apiResponse = [
            'analytics' => $analytics,
            'list' => $doctors->items(),
            'pagination' => [
                'total' => $doctors->total(),
                'per_page' => $doctors->perPage(),
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'from' => $doctors->firstItem(),
                'to' => $doctors->lastItem(),
            ],
        ];

        return $this->sendResponse($apiResponse, 'Doctor list fetched successfully');
    }


    private function getDoctorAnalytics(): array
    {
        // Total number of doctors
        $allDoctors = User::where('user_type', 'doctor')->count();

        // Count doctors by verification status
        $doctorStatuses = DB::table('doctor_profiles')
            ->select('verification_status', DB::raw('COUNT(*) as total'))
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status')
            ->toArray();

        // Ensure keys exist even if no doctors in that status
        $verifiedDoctor = $doctorStatuses['verified'] ?? 0;
        $pendingDoctor = $doctorStatuses['pending'] ?? 0;
        $unverifiedDoctor = $doctorStatuses['unverified'] ?? 0;
        $rejectedDoctor = $doctorStatuses['rejected'] ?? 0; // if needed

        return [
            'allDoctors' => $allDoctors,
            'verifiedDoctor' => $verifiedDoctor,
            'pendingDoctor' => $pendingDoctor,
            'unverifiedDoctor' => $unverifiedDoctor,
            'rejectedDoctor' => $rejectedDoctor,

        ];
    }

    //doctor Create for admin dashboard
    public function doctorDetails($id): \Illuminate\Http\JsonResponse
    {
        $doctor = User::with(['doctorProfile', 'address', 'personalDetails'])
            ->withCount([
                'assignedConsultations', // total assigned
                'assignedConsultations as completed_consultations_count' => function ($q) {
                    $q->where('consultation_status', 'completed');
                },
                'assignedConsultations as cancel_consultation' => function ($q) {
                    $q->where('consultation_status', 'cancelled');
                },
            ])
            ->find($id);
        if (!$doctor || $doctor->user_type !== 'doctor') {
            return $this->sendResponse([], 'Doctor not found');
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
        $plainPassword = Str::random(10);                    //  Generate random plain password
        $validated['password'] = Hash::make($plainPassword);       //  Save hashed password to DB
        $patient = User::create($validated);                      //  Create patient
        Mail::to($patient->email)->send(
            new DoctorAccountCreatedAdmin($patient->email, $plainPassword)       //  Send plain password by email
        );

        return $this->sendResponse([], __('Doctor created successfully.'));
    }
}
