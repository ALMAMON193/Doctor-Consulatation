<?php

namespace App\Http\Controllers\API\APP\Doctor;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\APP\Doctor\CreateProfileRequest;
use App\Http\Requests\APP\Doctor\MedicalInfoVerifyRequest;
use App\Models\DoctorProfile;
use App\Models\Specialization;
use App\Models\UserAddress;
use App\Models\UserPersonalDetail;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserApiController extends Controller
{
    use ApiResponse;
    /**
     * Store personal and account information for the authenticated doctor.
     *
     * @param CreateProfileRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function createProfile(CreateProfileRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        // Check if user is authenticated
        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }
        // Start transaction to ensure all or nothing operation
        DB::beginTransaction();
        try {
            // Save or update personal details
            UserPersonalDetail::updateOrCreate(
                ['user_id' => $user->id],
                $request->only('date_of_birth', 'cpf', 'gender', 'account_type')
            );
            // Save or update address and financial info
            UserAddress::updateOrCreate(
                ['user_id' => $user->id],
                $request->only(
                    'monthly_income',
                    'annual_income_for_company',
                    'company_telephone_number',
                    'business_name'
                )
            );
            //  Upload profile_picture to doctor_profiles
            $doctorProfileData = [];

            if ($request->hasFile('profile_picture')) {
                $profilePath = Helper::fileUpload($request->file('profile_picture'), 'doctor/profile_picture');
                $doctorProfileData['profile_picture'] = $profilePath;
            }

            DoctorProfile::updateOrCreate(
                ['user_id' => $user->id],
                $doctorProfileData
            );

            DB::commit();

            //  Return success response
            $personalData = $request->only('date_of_birth', 'cpf', 'gender', 'account_type');
            $personalData['profile_picture'] = asset(optional($user->doctorProfile)->profile_picture ?? '');

            $apiResponse = [
                'user_id'   => $user->id,
                'personal'  => $personalData,
                'financial' => $request->only(
                    'monthly_income',
                    'annual_income_for_company',
                    'company_telephone_number',
                    'business_name'
                ),
            ];
            // return success response
            return $this->sendResponse($apiResponse, 'Profile saved successfully');
        } catch (Exception $e) {
            // Rollback on error
            DB::rollBack();
            Log::error('CreateProfile: ' . $e->getMessage());
            return $this->sendError('Profile creation failed'.$e->getMessage(), [], 500);
        }
    }
    /**
     * Create or update doctor medical info for verification.
     *
     * @param \App\Http\Requests\APP\Doctor\MedicalInfoVerifyRequest $request
     * @return JsonResponse
     */
    public function medicalInfoVerify(MedicalInfoVerifyRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError('Only doctors may verify medical information', [], 403);
        }
        try {
            $validatedData = array_filter($request->validated(), fn($value) => !is_null($value));
            $doctorProfile = DoctorProfile::updateOrCreate(
                ['user_id' => $user->id],
                $validatedData
            );
            if ($request->hasFile('video_path')) {
                $videoPath = Helper::fileUpload($request->file('video_path'), 'doctor/presentation-video');
                $doctorProfile->update(['video_path' => $videoPath]);
            }
            // Prepare API response data array
            $apiResponse = [
                'medical_info' => $doctorProfile,
            ];
            $message = $doctorProfile->wasRecentlyCreated ? 'Medical info created successfully' : 'Medical info updated successfully';
            return $this->sendResponse([], $message);
        } catch (Exception $e) {
            Log::error('MedicalInfoVerify Error: ' . $e->getMessage());
            return $this->sendError('Medical info verification failed', [], 422);
        }
    }
    /**
     * Check doctor verification status and identify missing fields.
     *
     * @return JsonResponse
     */
    public function checkVerificationStatus(): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }

        $profile = DoctorProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            return $this->sendError('Profile not found', [], 404);
        }

        $labels = [
            'specialization'        => 'Medical Specialization',
            'cpf_bank'              => 'Bank CPF',
            'bank_name'             => 'Bank Name',
            'account_type'          => 'Account Type',
            'account_number'        => 'Account Number',
            'dv'                    => 'Verification Digit',
            'crm'                   => 'CRM Number',
            'current_account_number'=> 'Current Account Number',
            'current_dv'            => 'Current DV',
            'uf'                    => 'State Code (UF)',
            'zipcode'               => 'Address Zipcode',
            'address'               => 'Address',
            'house_number'          => 'House Number',
            'road_number'           => 'Road Number',
            'neighborhood'          => 'Neighborhood',
            'city'                  => 'City',
            'state'                 => 'State',
            'complement'            => 'Complement',
        ];

        $pending = [];
        $done    = [];

        foreach ($labels as $field => $label) {
            if (empty($profile->{$field})) {
                $pending[] = "Please complete the {$label} field";
            } else {
                $done[] = $label;
            }
        }

        // Determine verification status
        $verificationStatus = match (true) {
            $profile->verification_status === 'verified' => 'Verified',
            count($pending) > 0                          => 'Unverified',
            default                                      => 'Pending',
        };

        // Build response
        $apiResponse = [
            'user_id'             => $user->id,
            'user_name'           => $user->name,
            'profile_picture'     => ($user->doctorProfile && !empty($user->doctorProfile->profile_picture) && $user->doctorProfile->profile_picture !== 'null')
                ? asset('storage/' . $user->doctorProfile->profile_picture)
                : '',
            'verification_status' => $verificationStatus,
            'pending'             => $pending,
            'done'                => $done,
        ];

        return $this->sendResponse($apiResponse, 'Verification status retrieved successfully');
    }

    //get all specialization
    public function specializations(){
        $user = auth('sanctum')->user();
        // Ensure user is logged in
        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }
        try {
            $specializations = Specialization::select('id', 'name','price')->get();
            return $this->sendResponse ($specializations,__('Fetch All Specialization.'));
        }catch (Exception $e)
        {
            return $this->sendError ("Something Went To Wrong");
        }
    }
}
