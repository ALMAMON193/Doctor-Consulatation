<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Requests\DoctorFinancialRequest;
use App\Http\Requests\DoctorMedicalRequest;
use App\Http\Requests\DoctorProfileRequest;
use App\Helpers\Helper;
use App\Models\UserAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\DoctorProfile;
use App\Models\UserPersonalDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorEditRequest;
use Throwable;

class ProfileApiController extends Controller
{
    use ApiResponse;

    /**
     * Retrieve detailed profile information for the authenticated doctor.
     *
     * @return JsonResponse Response with profile details or error message
     */
    public function profileDetails(): JsonResponse
    {
        // Get currently authenticated user via Sanctum
        $user = auth('sanctum')->user();

        // Check if user exists and is a doctor
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can view their profile'), [], 403);
        }

        // Eager load relationships to reduce queries
        $user->loadMissing(['doctorProfile', 'personalDetail', 'address']);

        try {
            // Build API response array with grouped data sections
            $apiResponse = [
                'account_information' => [
                    'id'             => optional($user->doctorProfile)->id,
                    'user_id'        => $user->id,
                    'user_name'      => $user->name,
                    'email'          => $user->email,
                    'phone_number'   => $user->phone_number ?? '',
                    'profile_picture' => ($user->doctorProfile && !empty($user->doctorProfile->profile_picture) && $user->doctorProfile->profile_picture !== 'null')
                        ? asset('storage/' . $user->doctorProfile->profile_picture)
                        : '',
                ],

                'personal_information' => [
                    'date_of_birth' => optional($user->personalDetail)->date_of_birth,
                    'cpf'           => optional($user->personalDetail)->cpf,
                    'gender'        => optional($user->personalDetail)->gender,
                    'account_type'  => optional($user->personalDetail)->account_type,
                ],

                'legal_information' => [
                    'monthly_income'           => optional($user->address)->monthly_income,
                    'annual_income_company'    => optional($user->address)->annual_income_for_company,
                    'company_phone'            => optional($user->address)->company_telephone_number,
                    'company_name'             => optional($user->address)->business_name,
                ],

                'address_information' => [
                    'zipcode'      => optional($user->doctorProfile)->address_zipcode,
                    'number'       => optional($user->doctorProfile)->address_number,
                    'street'       => optional($user->doctorProfile)->address_street,
                    'neighborhood' => optional($user->doctorProfile)->address_neighborhood,
                    'complement'   => optional($user->doctorProfile)->address_complement,
                    'city'         => optional($user->doctorProfile)->address_city,
                    'state'        => optional($user->doctorProfile)->address_state,
                ],
            ];

            // Return success response with profile data
            return $this->sendResponse($apiResponse, __('Profile details retrieved successfully.'));
        } catch (Throwable $e) {
            // Log error and return generic error response
            Log::error('Error retrieving profile details: ' . $e->getMessage());
            return $this->sendError(__('Sorry, something went wrong.'), [], 500);
        }
    }

    /**
     * Update basic user information and personal details.
     *
     * @param DoctorProfileRequest $request Validated request containing user and personal details
     * @return JsonResponse Response with success or error message
     */
    public function updateProfileDetails(DoctorProfileRequest $request): JsonResponse
    {
        // Get authenticated user
        $user = auth('sanctum')->user();

        // Ensure user is doctor
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        // Use DB transaction to ensure atomicity of updates
        DB::beginTransaction();

        try {
            // Retrieve or create DoctorProfile model for current user
            $doctorProfile = DoctorProfile::firstOrCreate(['user_id' => $user->id]);

            // Prepare user data update array with mandatory name update
            $updateData = [
                'name' => $request->name,
            ];

            // If verification approved, restrict changing email and phone
            if ($doctorProfile->verification_status === 'approved') {
                if ($user->email !== $request->email) {
                    return $this->sendError(__('Email cannot be changed as it is already verified.'), [], 422);
                }
                if ($user->phone_number !== $request->phone_number) {
                    return $this->sendError(__('Phone number cannot be changed as it is already verified.'), [], 422);
                }
            } else {
                // Allow updating email and phone if not verified
                $updateData['email'] = $request->email;
                $updateData['phone_number'] = $request->phone_number;
            }

            // Fill user with update data
            $user->fill($updateData);

            // Handle avatar upload if present in request
            if ($request->hasFile('avatar')) {
                // Upload new avatar and delete old one
                $newAvatar = Helper::fileUpload($request->file('avatar'), 'doctor/avatar');
                Helper::fileDelete($user->avatar);
                $user->avatar = $newAvatar;
            }

            // Save user model updates
            $user->save();

            // Retrieve or create UserPersonalDetail for current user
            $personalDetails = UserPersonalDetail::firstOrCreate(['user_id' => $user->id]);

            // Prevent changing CPF if already verified
            if ($doctorProfile->verification_status === 'approved' && $personalDetails->cpf !== $request->cpf) {
                return $this->sendError(__('CPF cannot be changed as it is already verified.'), [], 422);
            }

            // Update or create personal detail fields
            UserPersonalDetail::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => $request->date_of_birth,
                    'cpf'          => $request->cpf,
                    'gender'       => $request->gender,
                    'account_type' => $request->account_type,
                ]
            );

            // Commit all changes
            DB::commit();

            // Return success response
            return $this->sendResponse([], __('Profile details updated successfully.'));
        } catch (\Exception $e) {
            // Rollback on failure and return error
            DB::rollBack();
            return $this->sendError(__('Sorry, something went wrong while updating profile details.'), [], 500);
        }
    }

    /**
     * Retrieve medical details for authenticated doctor.
     *
     * @return JsonResponse Response with medical info or error message
     */
    public function medicalDetails(): JsonResponse
    {
        // Get authenticated user
        $user = auth('sanctum')->user();

        // Check if user exists
        if (!$user) {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        // Eager load doctorProfile relation
        $user->loadMissing(['doctorProfile']);

        // Build response with medical info
        $apiResponse = [
            'medical_information' => [
                'crm'                      => optional($user->doctorProfile)->crm,
                'uf'                       => optional($user->doctorProfile)->uf,
                'specialization'           => optional($user->doctorProfile)->specialization,
                'presentation_video' => filled(optional($user->doctorProfile)->video_path)
                    ? asset('storage/' . $user->doctorProfile->video_path)
                    : '',
                'verification_status'      => optional($user->doctorProfile)->verification_status ?? 'pending',
                'verification_rejection_reason' => optional($user->doctorProfile)->verification_rejection_reason ?? '',
            ],
        ];

        // Return success response
        return $this->sendResponse($apiResponse, __('Medical Details retrieved successfully.'));
    }

    /**
     * Update medical information (CRM, UF, specialization, video).
     *
     * @param DoctorMedicalRequest $request Validated medical info request
     * @return JsonResponse Response with success or error message
     */
    public function medicalDataUpdate(DoctorMedicalRequest $request): JsonResponse
    {
        // Get authenticated user
        $user = auth('sanctum')->user();

        // Check user type is doctor
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        DB::beginTransaction();

        try {
            // Retrieve or create doctor profile
            $doctor = DoctorProfile::firstOrCreate(['user_id' => $user->id]);

            // Save original state to detect changes later
            $originalDoctor = $doctor->replicate();

            // Update medical fields
            $doctor->fill([
                'crm' => $request->crm,
                'uf' => $request->uf,
                'specialization' => $request->specialization,
            ]);

            $videoUpdated = false;

            // Handle video upload if present
            if ($request->hasFile('video_path')) {
                $newVideo = Helper::fileUpload($request->file('video_path'), 'doctor/videos');
                Helper::fileDelete($doctor->video_path); // Delete old video
                $doctor->video_path = $newVideo;
                $videoUpdated = true;
            }

            // Fields that affect verification status
            $medicalFields = ['crm', 'uf', 'specialization', 'video_path'];

            // Check if any medical field changed
            $fieldsUpdated = $doctor->isDirty($medicalFields);

            // If updated, reset verification status to pending
            if ($fieldsUpdated) {
                $doctor->verification_status = 'pending';
            }

            // Save changes
            $doctor->save();

            DB::commit();

            // Return appropriate message based on update
            if ($fieldsUpdated || $videoUpdated) {
                return $this->sendResponse([], __('Medical information has been updated and is pending verification.'));
            }

            return $this->sendResponse([], __('Medical information updated successfully.'));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError(__('Sorry, something went wrong while updating medical information.'), [], 500);
        }
    }

    /**
     * Retrieve financial details for the authenticated doctor.
     *
     * @return JsonResponse Response with financial info or error message
     */
    public function financialDetails(): JsonResponse
    {
        // Get authenticated user
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        // Eager load doctorProfile relation
        $user->loadMissing(['doctorProfile']);

        // Prepare financial information response
        $apiResponse = [
            'financial_information' => [
                'cpf'                    => $user->doctorProfile->cpf_bank,
                'bank'                   => $user->doctorProfile->bank_name,
                'account_type'           => $user->doctorProfile->account_type,
                'account_number'         => $user->doctorProfile->account_number,
                'dv'                     => $user->doctorProfile->dv,
                'current_account_number' => $user->doctorProfile->current_account_number ?? '',
                'current_dv'             => $user->doctorProfile->current_dv ?? '',
            ]
        ];

        return $this->sendResponse($apiResponse, __('Financial Details retrieved successfully.'));
    }

    /**
     * Update financial information for the authenticated doctor.
     *
     * @param DoctorFinancialRequest $request Validated financial data request
     * @return JsonResponse Response with success or error message
     */
    public function financialUpdate(DoctorFinancialRequest $request): JsonResponse
    {
        // Get authenticated user
        $user = auth('sanctum')->user();

        // Ensure user is a doctor
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        DB::beginTransaction();

        try {
            // Retrieve or create doctor profile
            $doctor = DoctorProfile::firstOrCreate(['user_id' => $user->id]);

            // Keep original data for change detection
            $originalDoctor = $doctor->replicate();

            // Fill financial data
            $doctor->fill([
                'cpf_bank'              => $request->cpf_bank,
                'bank_name'             => $request->bank_name,
                'account_type'          => $request->account_type,
                'account_number'        => $request->account_number,
                'dv'                    => $request->dv,
                'current_dv'            => $request->current_dv,
                'current_account_number'=> $request->current_account_number ?? '',
            ]);

            // Fields that require re-verification on change
            $financialFields = ['cpf_bank'];

            // Detect changes in financial fields
            $fieldsUpdated = $doctor->isDirty($financialFields);

            if ($fieldsUpdated) {
                $doctor->verification_status = 'pending';
            }

            $doctor->save();

            DB::commit();

            // Return success message, indicating verification if applicable
            if ($fieldsUpdated) {
                return $this->sendResponse([], __('Financial information has been updated and is pending verification.'));
            }

            return $this->sendResponse([], __('Financial information updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error for debugging
            Log::error($e->getMessage());

            return $this->sendError(__('Sorry, something went wrong while updating financial information.'), [], 500);
        }
    }
}
