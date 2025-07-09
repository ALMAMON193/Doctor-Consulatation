<?php

use App\Http\Controllers\API\Dashboard\Doctor\DoctorApiController;
use App\Http\Controllers\API\Dashboard\Patient\PatientApiController;
use App\Http\Controllers\API\Patient\ConsultationBookingController;
use App\Http\Controllers\API\Patient\MedicalApiRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthApiController;
use App\Http\Controllers\API\Doctor\UserApiController as DoctorUserApiController;
use App\Http\Controllers\API\Doctor\ProfileApiController as DoctorProfileApiController;
use App\Http\Controllers\API\Patient\ProfileApiController as PatientProfileApiController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthApiController::class, 'loginApi']);
    Route::post('register', [AuthApiController::class, 'registerApi']);
    Route::post('verify-email', [AuthApiController::class, 'verifyEmailApi']);
    Route::post('forgot-password', [AuthApiController::class, 'forgotPasswordApi']);
    Route::post('reset-password', [AuthApiController::class, 'resetPasswordApi']);
    Route::post('resend-otp', [AuthApiController::class, 'resendOtpApi']);
    Route::post('verify-otp', [AuthApiController::class, 'verifyOtpApi']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthApiController::class, 'logoutApi']);
});

    /*
  |--------------------------------------------------------------------------
  | Dashboard Doctor Routes
  |--------------------------------------------------------------------------
  */
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('doctor-list', [DoctorApiController::class, 'doctorList']);
        Route::get('doctor-details/{id}', [DoctorApiController::class, 'doctorDetails']);

    });


    /*
  |--------------------------------------------------------------------------
  | Dashboard Patients Routes
  |--------------------------------------------------------------------------
  */
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('patient-list', [PatientApiController::class, 'patientList']);
        Route::get('patient-details/{id}', [PatientApiController::class, 'doctorDetails']);

    });
    /*
    |--------------------------------------------------------------------------
    | Doctor Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('doctor')->middleware('doctor')->group(function () {
        Route::post('create-profile', [DoctorUserApiController::class, 'createProfile']);
        Route::post('medical-info-verify', [DoctorUserApiController::class, 'medicalInfoVerify']);
        Route::get('profile/verification-status', [DoctorUserApiController::class, 'checkVerificationStatus']);

        Route::get('profile/details', [DoctorProfileApiController::class, 'profileDetails']);
        Route::post('profile/update', [DoctorProfileApiController::class, 'updateProfileDetails']);
        Route::post('medical/update', [DoctorProfileApiController::class, 'medicalDataUpdate']);
        Route::post('financial/update', [DoctorProfileApiController::class, 'financialUpdate']);
    });

    /*
    |--------------------------------------------------------------------------
    | Patient Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('patient')->middleware('patient','auth:sanctum')->group(function () {
        Route::post('create-profile', [PatientProfileApiController::class, 'createProfile']);
        Route::get('profile/details', [PatientProfileApiController::class, 'profileDetails']);
        Route::get('accounts', [PatientProfileApiController::class, 'accountDetails']);
        Route::post('add/member/account', [PatientProfileApiController::class, 'addMemberAccount']);
        Route::post('update/member/account/{id}', [PatientProfileApiController::class, 'updateMemberAccount']);
        Route::delete('delete/member/account/{id}', [PatientProfileApiController::class, 'deleteMemberAccount']);
        Route::post('update/profile/details', [PatientProfileApiController::class, 'updateProfileDetails']);


        //consultation pay

        Route::post('/consultations', [ConsultationBookingController::class, 'book']);
        Route::post('/stripe/webhook', [ConsultationBookingController::class, 'handleWebhook'])->middleware('stripe.signature');
    });

    /*
     |--------------------------------------------------------------------------
     |Patient reports routes
     |--------------------------------------------------------------------------
     */
    Route::middleware('auth:sanctum')->prefix('patient/medical-record')->group(function () {
        Route::get('/', [MedicalApiRecordController::class, 'index']);
        Route::post('/store', [MedicalApiRecordController::class, 'storeMedicalRecord']);
        Route::post('/update/{id}', [MedicalApiRecordController::class, 'updateMedicalRecord']);
        Route::delete('/delete/{id}', [MedicalApiRecordController::class, 'destroyMedicalRecord']);
    });


