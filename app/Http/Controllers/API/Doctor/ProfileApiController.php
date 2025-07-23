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
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'doctor') {
            return $this->sendError(__('Only doctors can view their profile'), [], 403);
        }

        // eager load
        $user->loadMissing(['doctorProfile', 'personalDetail', 'address']);

        try {
            $apiResponse = [
                'account_information' => [
                    'id'             => optional($user->doctorProfile)->id,
                    'user_id'        => $user->id,
                    'user_name'      => $user->name,
                    'email'          => $user->email,
                    'phone_number'   => $user->phone_number ?? '',
                    'profile_picture'=> optional($user->doctorProfile)->profile_picture
                        ? asset($user->doctorProfile->profile_picture)
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

                'medical_information' => [
                    'crm'          => optional($user->doctorProfile)->crm,
                    'uf'           => optional($user->doctorProfile)->uf,
                    'specialization'=> optional($user->doctorProfile)->specialization,
                    'presentation_video' =>
                        optional($user->doctorProfile)->video_path
                            ? asset($user->doctorProfile->video_path)
                            : null,
                    'verification_status' =>
                        optional($user->doctorProfile)->verification_status ?? 'pending',
                    'verification_rejection_reason' =>
                        optional($user->doctorProfile)->verification_rejection_reason,
                ],

                'financial_information' => [
                    'cpf_bank'      => optional($user->doctorProfile)->cpf_bank,
                    'bank_name'     => optional($user->doctorProfile)->bank_name,
                    'account_type'  => optional($user->doctorProfile)->account_type,
                    'account_number'=> optional($user->doctorProfile)->account_number,
                    'dv'            => optional($user->doctorProfile)->dv,
                ],
            ];

            return $this->sendResponse($apiResponse, __('Profile details retrieved successfully.'));
        } catch (Throwable $e) {
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
