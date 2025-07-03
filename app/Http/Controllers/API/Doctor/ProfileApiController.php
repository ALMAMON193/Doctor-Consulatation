<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Requests\DoctorFinancialRequest;
use App\Http\Requests\DoctorMedicalRequest;
use App\Http\Requests\DoctorProfileRequest;
use Exception;
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
        // Ensure only authenticated doctors can view their profile
        $user = auth('sanctum')->user();
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can view their profile'), [], 403);
        }

        // Load all profile data with null checks
        $doctorProfile = DoctorProfile::where('user_id', $user->id)->first();
        $personalDetails = UserPersonalDetail::where('user_id', $user->id)->first();
        $userAddress = UserAddress::where('user_id', $user->id)->first();

        try {
            $apiResponse = [
                'account_information' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number ?? '',
                    'avatar' => $user->avatar ? asset($user->avatar) : '',
                ],
                'personal_information' => [
                    'date_of_birth' => $personalDetails ? $personalDetails->date_of_birth : null,
                    'cpf' => $personalDetails ? $personalDetails->cpf : null,
                    'gender' => $personalDetails ? $personalDetails->gender : null,
                    'account_type' => $personalDetails ? $personalDetails->account_type : null,
                ],
                'legal_information' => [
                    'monthly_income' => $userAddress ? $userAddress->monthly_income : null,
                    'annual_income_company' => $userAddress ? $userAddress->annual_income_for_company : null,
                    'company_phone' => $userAddress ? $userAddress->company_telephone_number : null,
                    'company_name' => $userAddress ? $userAddress->business_name : null,
                ],
                'address_information' => [
                    'zipcode' => $doctorProfile ? $doctorProfile->address_zipcode : null,
                    'number' => $doctorProfile ? $doctorProfile->address_number : null,
                    'street' => $doctorProfile ? $doctorProfile->address_street : null,
                    'neighborhood' => $doctorProfile ? $doctorProfile->address_neighborhood : null,
                    'complement' => $doctorProfile ? $doctorProfile->address_complement : null,
                    'city' => $doctorProfile ? $doctorProfile->address_city : null,
                    'state' => $doctorProfile ? $doctorProfile->address_state : null,
                ],
                'medical_information' => [
                    'crm' => $doctorProfile ? $doctorProfile->crm : null,
                    'uf' => $doctorProfile ? $doctorProfile->uf : null,
                    'specialization' => $doctorProfile ? $doctorProfile->specialization : null,
                    'presentation_video' => $doctorProfile && $doctorProfile->video_path ? asset($doctorProfile->video_path) : null,
                    'verification_status' => $doctorProfile ? $doctorProfile->verification_status : 'pending',
                    'verification_rejection_reason' => $doctorProfile ? $doctorProfile->verification_rejection_reason : null,
                ],
                'financial_information' => [
                    'cpf_bank' => $doctorProfile ? $doctorProfile->cpf_bank : null,
                    'bank_name' => $doctorProfile ? $doctorProfile->bank_name : null,
                    'account_type' => $doctorProfile ? $doctorProfile->account_type : null,
                    'account_number' => $doctorProfile ? $doctorProfile->account_number : null,
                    'dv' => $doctorProfile ? $doctorProfile->dv : null,
                ],
            ];

            $message = __('Profile details retrieved successfully.');

            return $this->sendResponse($apiResponse, $message);
        } catch (Exception $e) {
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
        // Ensure only authenticated doctors can update their profile
        $user = auth('sanctum')->user();
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        // Start a transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Retrieve or create doctor profile to check verification status
            $doctorProfile = DoctorProfile::firstOrCreate(['user_id' => $user->id]);

            // === Update User Table ===
            // Update basic user information (name only, email and phone if not approved)
            $updateData = [
                'name' => $request->name,
            ];

            // Check if verification status is approved
            if ($doctorProfile->verification_status === 'approved') {
                if ($user->email !== $request->email) {
                    return $this->sendError(__('Email cannot be changed as it is already verified.'), [], 422);
                }
                if ($user->phone_number !== $request->phone_number) {
                    return $this->sendError(__('Phone number cannot be changed as it is already verified.'), [], 422);
                }
            } else {
                $updateData['email'] = $request->email;
                $updateData['phone_number'] = $request->phone_number;
            }

            $user->fill($updateData);

            // Handle avatar upload if provided
            if ($request->hasFile('avatar')) {
                $newAvatar = Helper::fileUpload($request->file('avatar'), 'doctor/avatar');
                Helper::fileDelete($user->avatar); // Delete old avatar after successful upload
                $user->avatar = $newAvatar;
            }

            $user->save();

            // === Update Personal Details ===
            // Retrieve or create personal details
            $personalDetails = UserPersonalDetail::firstOrCreate(['user_id' => $user->id]);

            // Check if CPF is verified (assuming CPF is verified if status is approved)
            if ($doctorProfile->verification_status === 'approved' && $personalDetails->cpf !== $request->cpf) {
                return $this->sendError(__('CPF cannot be changed as it is already verified.'), [], 422);
            }
            // Update or create personal details (date of birth, CPF, gender, account type)
            UserPersonalDetail::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => $request->date_of_birth,
                    'cpf' => $request->cpf,
                    'gender' => $request->gender,
                    'account_type' => $request->account_type,
                ]
            );

            // Commit transaction and return success response
            DB::commit();
            return $this->sendResponse([], __('Profile details updated successfully.'));
        } catch (Exception $e) {
            // Rollback transaction on error and return error response
            DB::rollBack();
            return $this->sendError(__('Sorry, something went wrong while updating profile details.'), [], 500);
        }
    }


    /**
     * Update medical information (CRM, UF, specialization, video).
     *
     * @param DoctorMedicalRequest $request Validated request containing medical details
     * @return JsonResponse Response with success or error message
     */
    public function medicalDataUpdate(DoctorMedicalRequest $request): JsonResponse
    {
        // Ensure only authenticated doctors can update their profile
        $user = auth('sanctum')->user();
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }

        // Start a transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // === Update Doctor Profile (Medical) ===
            // Retrieve or create doctor profile for medical fields
            $doctor = DoctorProfile::firstOrCreate(['user_id' => $user->id]);

            // Store original data to track changes
            $originalDoctor = $doctor->replicate();

            // Update medical fields
            $doctor->fill([
                'crm' => $request->crm,
                'uf' => $request->uf,
                'specialization' => $request->specialization,
            ]);

            // Handle video upload if provided
            $videoUpdated = false;
            if ($request->hasFile('video_path')) {
                $newVideo = Helper::fileUpload($request->file('video_path'), 'doctor/videos');
                Helper::fileDelete($doctor->video_path); // Delete old video after successful upload
                $doctor->video_path = $newVideo;
                $videoUpdated = true;
            }

            // Check if medical fields have changed, requiring re-verification
            $medicalFields = ['crm', 'uf', 'specialization', 'video_path'];
            $fieldsUpdated = $doctor->isDirty($medicalFields);

            if ($fieldsUpdated) {
                $doctor->verification_status = 'pending';

            }
            $doctor->save();
            // Commit transaction and return success response with appropriate message
            DB::commit();

            if ($fieldsUpdated || $videoUpdated) {
                return $this->sendResponse([], __('Medical information has been updated and is pending verification.'));
            }
            return $this->sendResponse([], __('Medical information updated successfully.'));
        } catch (Exception $e) {
            // Rollback transaction on error and return error response
            DB::rollBack();
            return $this->sendError(__('Sorry, something went wrong while updating medical information.'), [], 500);
        }
    }

    public function financialUpdate(DoctorFinancialRequest $request): JsonResponse
    {
        // Ensure only authenticated doctors can update their profile
        $user = auth('sanctum')->user();
        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can edit their profile'), [], 403);
        }
        // Start a transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // === Update Doctor Profile (Financial) ===
            // Retrieve or create doctor profile for financial fields
            $doctor = DoctorProfile::firstOrCreate(['user_id' => $user->id]);

            // Store original data to track changes
            $originalDoctor = $doctor->replicate();

            // Update financial fields
            $doctor->fill([
                'cpf_bank' => $request->cpf_bank,
                'bank_name' => $request->bank_name,
                'account_type' => $request->account_type,
                'account_number' => $request->account_number,
                'dv' => $request->dv,
            ]);

            // Check if financial fields have changed, requiring re-verification
            $financialFields = ['cpf_bank', 'bank_name', 'account_type', 'account_number', 'dv'];
            $fieldsUpdated = $doctor->isDirty($financialFields);

            if ($fieldsUpdated) {
                $doctor->verification_status = 'pending';
            }
            $doctor->save();
            // Commit transaction and return success response with appropriate message
            DB::commit();
            if ($fieldsUpdated) {
                return $this->sendResponse([], __('Financial information has been updated and is pending verification.'));
            }
            return $this->sendResponse([], __('Financial information updated successfully.'));
        } catch (Exception $e) {
            // Rollback transaction on error and return error response
            DB::rollBack();
            Log::error($e->getMessage());
            return $this->sendError(__('Sorry, something went wrong while updating financial information.'), [], 500);
        }
    }
}
