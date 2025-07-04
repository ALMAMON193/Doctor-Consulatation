<?php

namespace App\Http\Controllers\API\Patient;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalRecordStoreRequest;
use App\Http\Requests\MedicalRecordUpdateRequest;
use App\Models\Patient;
use App\Models\PatientMember;
use App\Models\PatientMedicalRecord;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class MedicalApiRecordController extends Controller
{
    use ApiResponse;
    /**
     * List all medical records (for patient or patient member)
     */
    public function index(): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->sendError('User not found', [], 404);
        }

        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->sendError('Patient profile not found', [], 404);
        }

        $records = PatientMedicalRecord::where('patient_id', $patient->id)
            ->orWhereIn('patient_member_id', $patient->patientMembers->pluck('id'))
            ->latest()
            ->get();

        $apiResponse = [];

        foreach ($records as $record) {
            if ($record->patient_member_id) {
                $member = PatientMember::find($record->patient_member_id);
                $name = $member?->name ?? 'Unknown Member';
                $photo = $member?->profile_photo ? asset($member->profile_photo) : null;
            } else {
                $name = $patient->user->name ?? 'Patient';
                $photo = $patient->profile_photo ? asset($patient->profile_photo) : null;
            }

            $apiResponse[] = [
                'id' => $record->id,
                'name' => $name,
                'record_type' => $record->record_type,
                'record_date' => $record->record_date,
                'file_path' => $record->file_path ? asset($record->file_path) : null,
                'profile_photo' => $photo,
            ];
        }

        return $this->sendResponse($apiResponse, 'Medical records retrieved successfully');
    }

    /**
     * Store a new medical record
     */
    public function storeMedicalRecord(MedicalRecordStoreRequest $request): JsonResponse
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

            $data = $request->only(['record_type', 'record_date']);

            if ($request->hasFile('file_path')) {
                $data['file_path'] = Helper::fileUpload($request->file('file_path'), 'patient/medical_records');
            }

            if ($request->filled('patient_member_id')) {
                $member = PatientMember::where('id', $request->patient_member_id)
                    ->where('patient_id', $patient->id)
                    ->first();

                if (!$member) {
                    return $this->sendError(__('Member not found'), [], 404);
                }

                $data['patient_member_id'] = $member->id;
            } else {
                $data['patient_id'] = $patient->id;
            }
            $record = PatientMedicalRecord::create($data);

            DB::commit();
            $apiResponse = [
                'id' => $record->id,
                'record_type' => $record->record_type,
                'record_date' => $record->record_date,
                'file_path' => $record->file_path ? asset($record->file_path) : null,
                'member_id' => $record->patient_member_id,
                'patient_id' => $patient->id,
            ];
            return $this->sendResponse($apiResponse, __('Medical record added successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError(__('Failed to add medical record'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a medical record
     */
    public function updateMedicalRecord(MedicalRecordUpdateRequest $request, $id): JsonResponse
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

            $record = PatientMedicalRecord::where(function ($query) use ($patient) {
                $query->where('patient_id', $patient->id)
                    ->orWhereIn('patient_member_id', $patient->patientMembers->pluck('id'));
            })->find($id);

            if (!$record) {
                return $this->sendError(__('Medical record not found'), [], 404);
            }

            $data = $request->only(['record_type', 'record_date']);

            if ($request->hasFile('file_path')) {
                if (!empty($record->file_path)) {
                    Helper::fileDelete($record->file_path);
                }

                $data['file_path'] = Helper::fileUpload(
                    $request->file('file_path'),
                    'patient/medical_records'
                );
            }

            $record->update($data);

            DB::commit();

            return $this->sendResponse($record, __('Medical record updated successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError(__('Failed to update medical record'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a medical record
     */
    public function destroyMedicalRecord($id): JsonResponse
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

            $record = PatientMedicalRecord::where(function ($query) use ($patient) {
                $query->where('patient_id', $patient->id)
                    ->orWhereIn('patient_member_id', $patient->patientMembers->pluck('id'));
            })->find($id);

            if (!$record) {
                return $this->sendError(__('Medical record not found'), [], 404);
            }

            if (!empty($record->file_path)) {
                Helper::fileDelete($record->file_path);
            }

            $record->delete();

            DB::commit();

            return $this->sendResponse([], __('Medical record deleted successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->sendError(__('Failed to delete medical record'), ['error' => $e->getMessage()], 500);
        }
    }
}
