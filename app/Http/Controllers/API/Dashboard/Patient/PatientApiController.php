<?php

namespace App\Http\Controllers\API\Dashboard\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientListResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientApiController extends Controller
{
    use ApiResponse;

    public function patientList(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        // 📊 Get analytics summary
        $analytics = $this->getPatientAnalytics();

        // 👤 Get patient list with pagination
        $patients = User::with('patient') // Assuming you have a `patient()` relationship in User model
        ->where('user_type', 'patient')
            ->paginate($perPage);

        // 🧼 Apply resource formatting
        $collection = PatientListResource::collection($patients)->resolve();
        $patients->setCollection(collect($collection));

        return $this->sendResponse([
            'analytics' => $analytics,
            'list' => $patients->items(),
        ], __('Patient list fetched successfully.'));
    }

    private function getPatientAnalytics(): array
    {
        $allPatients = User::where('user_type', 'patient')->count();

        $statuses = DB::table('patients')
            ->selectRaw("
                SUM(verification_status = 'approved')  as verified,
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

}
