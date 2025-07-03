<?php

namespace App\Http\Controllers\API\Doctor;

use Exception;
use App\Helpers\Helper;
use App\Models\UserAddress;
use App\Traits\ApiResponse;
use App\Models\DoctorProfile;
use App\Models\UserPersonalDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProfileRequest;
use App\Http\Requests\MedicalInfoVerifyRequest;

class UserApiController extends Controller
{
    use ApiResponse;

    /**
     * Store personal and account information for the authenticated doctor.
     *
     * @param CreateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createProfile(CreateProfileRequest $request)
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

            // Upload and update avatar image if provided
            if ($request->hasFile('avatar')) {
                $path = Helper::fileUpload($request->file('avatar'), 'doctor/avatar');
                $user->avatar = $path;
                $user->save();
            }

            // Commit transaction after successful operations
            DB::commit();

            //create success response
            $apiResponse = [
                'user_id'   => $user->id,
                'avatar'    => asset($user->avatar ?? ''),
                'personal'  => $request->only('date_of_birth', 'cpf', 'gender', 'account_type'),
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
            return $this->sendError('Profile creation failed', [], 500);
        }
    }

    /**
     * Create or update doctor medical info for verification.
     *
     * @param MedicalInfoVerifyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function medicalInfoVerify(MedicalInfoVerifyRequest $request)
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
                'user_id' => $user->id,
                'medical_info' => $doctorProfile,
            ];

            $message = $doctorProfile->wasRecentlyCreated ? 'Medical info created successfully' : 'Medical info updated successfully';

            return $this->sendResponse($apiResponse, $message);
        } catch (Exception $e) {
            Log::error('MedicalInfoVerify Error: ' . $e->getMessage());
            return $this->sendError('Medical info verification failed', [], 422);
        }
    }
    /**
     * Check doctor verification status and identify missing fields.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkVerificationStatus()
    {
        $user = auth('sanctum')->user();

        // Ensure user is logged in
        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }

        // Fetch doctor's profile
        $profile = DoctorProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            return $this->sendError('Profile not found', [], 404);
        }

        // List of required fields and their human-friendly labels
        $labels = [
            'additional_medical_record_number' => 'Additional Medical Record Number',
            'specialization'                   => 'Medical Specialization',
            'cpf_bank'                         => 'Bank CPF',
            'bank_name'                        => 'Bank Name',
            'account_type'                     => 'Account Type',
            'account_number'                   => 'Account Number',
            'dv'                               => 'Verification Digit',
            'crm'                              => 'CRM Number',
            'uf'                               => 'State Code (UF)',
            'monthly_income'                   => 'Monthly Income',
            'company_income'                   => 'Company Income',
            'company_phone'                    => 'Company Phone Number',
            'company_name'                     => 'Company Name',
            'address_zipcode'                  => 'Address Zipcode',
            'address_number'                   => 'Address Number',
            'address_street'                   => 'Address Street',
            'address_neighborhood'             => 'Address Neighborhood',
            'address_city'                     => 'Address City',
            'address_state'                    => 'Address State',
            'address_complement'               => 'Address Complement',
            'personal_name'                    => 'Personal Name',
            'date_of_birth'                    => 'Date of Birth',
            'cpf_personal'                     => 'Personal CPF',
            'email'                            => 'Email Address',
            'phone_number'                     => 'Phone Number',
        ];

        // Build list of missing fields
        $missing = collect($labels)
            ->filter(fn($label, $field) => is_null($profile->{$field}))
            ->map(fn($label) => "Please complete the {$label} field")
            ->values()
            ->all();

        // Determine overall verification status
        $verificationStatus = match (true) {
            $profile->verification_status === 'approved' => 'Verified',
            $missing                                      => 'Unverified',
            default                                       => 'Pending',
        };

        // create response data
        $apiResponse = [
            'user_id'             => $user->id,
            'user_name'           => $user->name,
            'avatar'              => $user->avatar ? asset($user->avatar) : '',
            'verification_status' => $verificationStatus,
            $missing ? 'pending' : 'done' => $missing ?: [],
        ];
        return $this->sendResponse($apiResponse, 'Verification status retrieved successfully');
    }
}
