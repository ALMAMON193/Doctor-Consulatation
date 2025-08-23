<?php

namespace App\Http\Controllers\API\Dashboard\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Doctor\StoreRequest;
use App\Http\Resources\Dashboard\Patient\DetailsPatientResource;
use App\Http\Resources\Dashboard\Patient\PatientListResource;

use App\Mail\PatientAccountCreatedAdmin;
use App\Models\Patient;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PatientApiController extends Controller
{
    use ApiResponse;

    public function patientList(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $statusFilter = $request->input('status');

        // Analytics
        $analytics = $this->getPatientAnalytics();

        // Query patients
        $patients = User::with('patient')
            ->where('user_type', 'patient')
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->whereHas('patient', function ($q2) use ($statusFilter) {
                    $q2->where('verification_status', $statusFilter);
                });
            })
            ->paginate($perPage);
        // Transform collection using resource
        $data = PatientListResource::collection($patients->items());
        // Pagination info
        $pagination = [
            'total' => $patients->total(),
            'per_page' => $patients->perPage(),
            'current_page' => $patients->currentPage(),
            'last_page' => $patients->lastPage(),
            'from' => $patients->firstItem(),
            'to' => $patients->lastItem(),
        ];

        return $this->sendResponse([
            'analytics' => $analytics,
            'data' => $data,
            'pagination' => $pagination,
        ], __('Patient list fetched successfully.'));
    }


    private function getPatientAnalytics(): array
    {
        $allPatients = User::where('user_type', 'patient')->count();

        $statuses = DB::table('patients')
            ->selectRaw("
                SUM(verification_status = 'verified')  as verified,
                SUM(verification_status = 'pending')   as pending,
                SUM(verification_status = 'rejected')  as unverified
            ")
            ->first();

        return [
            'all_patients' => $allPatients,
            'verified' => (int)$statuses->verified,
            'pending' => (int)$statuses->pending,
            'unverified' => (int)$statuses->unverified,
        ];
    }

    public function patientDetails($id): \Illuminate\Http\JsonResponse
    {
        $patient = Patient::with(['user', 'members', 'medicalRecords'])
            ->where('user_id', $id)
            ->first();
        if (!$patient) {
            return $this->sendError('Patient not found', [], 404);
        }
        return $this->sendResponse(new DetailsPatientResource($patient), 'Patient details fetched successfully');
    }

    public function createPatient(StoreRequest $request): \Illuminate\Http\JsonResponse
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
            new PatientAccountCreatedAdmin($patient->email, $plainPassword)       //  Send plain password by email
        );

        return $this->sendResponse([], __('Patient created successfully and credentials emailed.'));
    }

}
