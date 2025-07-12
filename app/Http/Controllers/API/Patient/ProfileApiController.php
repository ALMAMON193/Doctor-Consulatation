<?php

namespace App\Http\Controllers\API\Patient;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddMemberAccountRequest;
use App\Http\Requests\EditMemberAccountRequest;
use App\Http\Requests\PatientCreateAccountRequest;
use App\Http\Requests\PatientEditInfoRequest;
use App\Models\DoctorProfile;
use App\Models\Patient;
use App\Models\PatientMember;
use App\Models\UserPersonalDetail;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProfileApiController extends Controller
{
    use ApiResponse;

    public function profileDetails(): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'patient') {
            return $this->sendError(__('Only patients can access profile details'), [], 403);
        }

        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->sendError(__('Profile not found'), [], 404);
        }

        $formattedDob = $patient->date_of_birth
            ? Carbon::parse($patient->date_of_birth)->format('Y-d-m')
            : null;

        $apiResponse = [
            'user_information' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ],
            'personal_information' => [
                'date_of_birth' => $formattedDob,
                'cpf' => $patient->cpf,
                'gender' => $patient->gender,
                'mother_name' => $patient->mother_name,
            ],
            'address_information' => [
                'zipcode' => $patient->zipcode,
                'house_number' => $patient->house_number,
                'road' => $patient->road,
                'neighborhood' => $patient->neighborhood,
                'complement' => $patient->complement,
                'city' => $patient->city,
                'state' => $patient->state,
            ],
            'file_upload' => [
                'profile_photo' => $patient->profile_photo
                    ? asset($patient->profile_photo)
                    : null,
            ],

        ];

        return $this->sendResponse($apiResponse, __('Profile details successfully'));
    }

    //profile create for patient
    public function createProfile(PatientCreateAccountRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->user_type !== 'patient') {
            return $this->sendError(__('Only patients can create their profile'), [], 403);
        }

        DB::beginTransaction();

        try {
            // ------------------------- Build the data payload -------------------------
            $data = $request->only([
                'date_of_birth',
                'cpf',
                'gender',
                'mother_name',
                'zipcode',
                'house_number',
                'road',
                'neighborhood',
                'complement',
                'city',
                'state',
                'account_type',
            ]);

            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = Helper::fileUpload(
                    $request->file('profile_photo'),
                    'patient/profile_photo'
                );
            }

            $data['user_id'] = $user->id;

            // ------------------------- Create or update profile -----------------------
            $patient = Patient::updateOrCreate(
                ['user_id' => $user->id],
                $data
            );

            DB::commit();

            // ------------------------- Build response payload ------------------------
            $response = [
                'personal_information' => [
                    'date_of_birth' => $patient->date_of_birth
                        ? Carbon::parse($patient->date_of_birth)->format('Y-m-d')
                        : null,
                    'cpf' => $patient->cpf,
                    'gender' => $patient->gender,
                    'mother_name' => $patient->mother_name,
                ],
                'address_information' => [
                    'zipcode' => $patient->zipcode,
                    'house_number' => $patient->house_number,
                    'road' => $patient->road,
                    'neighborhood' => $patient->neighborhood,
                    'complement' => $patient->complement,
                    'city' => $patient->city,
                    'state' => $patient->state,
                ],
                'file_upload' => [
                    'profile_photo' => $patient->profile_photo
                        ? asset($patient->profile_photo)
                        : null,
                ],
            ];

            return $this->sendResponse($response, __('Profile created successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError(
                __('Sorry, something went wrong'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    //update profile (personal information , address,account information)
    public function updateProfileDetails(PatientEditInfoRequest $request): JsonResponse
    {
        // Get the authenticated user
        $user = auth('sanctum')->user();
        // ─────────────────────────────────────────────────────────────
        // 1. Authorization check — only users with type 'patient' allowed
        // ─────────────────────────────────────────────────────────────
        if (!$user || $user->user_type !== 'patient') {
            return $this->sendError(__('Only patients can edit their profile.'), [], 403);
        }
        try {
            DB::beginTransaction();
            // ─────────────────────────────────────────────────────────────
            // 2. Get or create patient profile (linked via user_id)
            // ─────────────────────────────────────────────────────────────
            $patient = Patient::firstOrCreate(['user_id' => $user->id]);

            // ─────────────────────────────────────────────────────────────
            // 4. Handle profile photo upload (patient)
            // ─────────────────────────────────────────────────────────────
            if ($request->hasFile('profile_photo')) {
                // Upload new image
                $newPhoto = Helper::fileUpload($request->file('profile_photo'), 'patient/profile_photo');

                // Only delete old photo if it exists
                if ($patient->profile_photo) {
                    Helper::fileDelete($patient->profile_photo);
                }
                // Set new photo path
                $patient->profile_photo = $newPhoto;
            }

            // ─────────────────────────────────────────────────────────────
            // 5. Update patient profile fields
            // ─────────────────────────────────────────────────────────────
            $patient->fill([
                'date_of_birth' => $request->date_of_birth,
                'cpf' => $request->cpf,
                'gender' => $request->gender,
                'mother_name' => $request->mother_name,
                'zipcode' => $request->zipcode,
                'house_number' => $request->house_number,
                'road' => $request->road,
                'neighborhood' => $request->neighborhood,
                'complement' => $request->complement,
                'city' => $request->city,
                'state' => $request->state,
            ])->save();

            // ─────────────────────────────────────────────────────────────
            // 6. Update user fields (users table)
            // ─────────────────────────────────────────────────────────────
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);
            DB::commit();
            return $this->sendResponse([], __('Profile details updated successfully.'));
        } catch (Throwable $e) {
            DB::rollBack();
            // Log the actual error for debugging
            Log::error('Patient profile update error: ' . $e->getMessage());
            // Return generic error to client
            return $this->sendError(__('Something went wrong while updating profile details.' . $e->getMessage()), [], 500);
        }
    }

    public function accountDetails(): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('User not found'), [], 404);
        }

        if ($user->user_type !== 'patient') {
            return $this->sendError(__('Only patients can create their profile'), [], 403);
        }

        $patient = Patient::with('patientMembers', 'user')->where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->sendError(__('Patient profile not found'), [], 404);
        }

        $apiResponse = [
            'my_account' => [
                'id' => $patient->id,
                'name' => $patient->user->name,
                'relationship' => "Main Account",
                'profile_photo' => $patient->profile_photo ? asset($patient->profile_photo) : '',
            ],
            'family_members' => $patient->patientMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'relationship' => $member->relationship,
                    'profile_photo' => $member->profile_photo ? asset($member->profile_photo) : '',
                ];
            })->toArray(),
        ];

        return $this->sendResponse($apiResponse, __('Patient details retrieved successfully'));
    }

    //add member account
    public function addMemberAccount(AddMemberAccountRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('User not found'), [], 404);
        }
        DB::beginTransaction();

        try {
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return $this->sendError(__('Patient profile not found'), [], 404);
            }

            $data = $request->only([
                'name',
                'date_of_birth',
                'relationship',
                'cpf',
                'gender',
            ]);

            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = Helper::fileUpload($request->file('profile_photo'), 'patient/member_photos');
            }

            $data['patient_id'] = $patient->id;

            $member = PatientMember::create($data);

            DB::commit();

            $response = [
                'id' => $member->id,
                'name' => $member->name,
                'relationship' => $member->relationship,
                'date_of_birth' => $member->date_of_birth,
                'cpf' => $member->cpf,
                'gender' => $member->gender,
                'profile_photo' => $member->profile_photo ? asset($member->profile_photo) : null,
            ];

            return $this->sendResponse($response, __('Member added successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError(__('Failed to add member'), ['error' => $e->getMessage()], 500);
        }
    }

   //update member account
    public function updateMemberAccount(EditMemberAccountRequest $request, $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError(__('User not found'), [], 404);
        }

        // 1. Load the patient for this user
        $patient = Patient::where('user_id', $user->id)->first();
        if (!$patient) {
            return $this->sendError(__('Patient profile not found'), [], 404);
        }

        // 2. Load the member that belongs to this patient
        $member = PatientMember::where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$member) {
            return $this->sendError(__('Member not found'), [], 404);
        }

        DB::beginTransaction();

        try {
            // 3. Build update payload
            $data = $request->only([
                'name',
                'date_of_birth',
                'relationship',
                'gender',
            ]);

            // 4. Handle new profile photo
            if ($request->hasFile('profile_photo')) {
                if (!empty($member->profile_photo)) {
                    Helper::fileDelete($member->profile_photo);
                }
                $data['profile_photo'] = Helper::fileUpload(
                    $request->file('profile_photo'),
                    'patient/member_photos'
                );
            }

            // 5. Update & commit
            $member->update($data);
            DB::commit();

            $apiResponse = [
                'id' => $member->id,
                'name' => $member->name,
                'relationship' => $member->relationship,
                'date_of_birth' => $member->date_of_birth,
                'cpf' => $member->cpf,
                'gender' => $member->gender,
                'profile_photo' => $member->profile_photo ? asset($member->profile_photo) : null,
            ];

            return $this->sendResponse($apiResponse, __('Member updated successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to update member: '.$e->getMessage());
            return $this->sendError(
                __('Failed to update member'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    //delete member
    public function deleteMemberAccount($id): JsonResponse
    {
        // Get the authenticated user
        $user = auth('sanctum')->user();
        // Check if user is logged in
        if (!$user) {
            return $this->sendError(__('User not found'), [], 400);
        }
        // Find the member by ID or fail with 404
        $member = PatientMember::findOrFail($id);
        // Start DB transaction
        DB::beginTransaction();
        try {
            // Delete profile photo file if it exists
            if ($member->profile_photo) {
                Helper::fileDelete($member->profile_photo);
            }
            // Delete the member record
            $member->delete();
            // Commit transaction
            DB::commit();
            return $this->sendResponse([], __('Member deleted successfully.'));
        } catch (Throwable $e) {
            // Rollback on error
            DB::rollBack();
            // Log the error for debugging
            Log::error('Failed to delete patient member: ' . $e->getMessage());
            return $this->sendError(__('Failed to delete member'), [], 500);
        }
    }

}
