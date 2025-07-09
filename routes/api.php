<?php

use App\Http\Controllers\API\Doctor\CouponApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthApiController;
use App\Http\Controllers\API\Dashboard\Doctor\DoctorApiController;
use App\Http\Controllers\API\Dashboard\Patient\PatientApiController;
use App\Http\Controllers\API\Doctor\UserApiController as DoctorUserApiController;
use App\Http\Controllers\API\Doctor\ProfileApiController as DoctorProfileApiController;
use App\Http\Controllers\API\Patient\ProfileApiController as PatientProfileApiController;
use App\Http\Controllers\API\Patient\ConsultationBookingController;
use App\Http\Controllers\API\Patient\MedicalApiRecordController;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
| Routes for user registration, login, password reset, and email verification.
| Accessible without authentication.
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
| Authenticated Routes
|--------------------------------------------------------------------------
| Routes that require Sanctum authentication.
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthApiController::class, 'logoutApi']);
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard - Doctor Management Routes
|--------------------------------------------------------------------------
| Admin-only routes to manage doctor-related data.
*/
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('doctor-list', [DoctorApiController::class, 'doctorList']);
    Route::get('doctor-details/{id}', [DoctorApiController::class, 'doctorDetails']);
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard - Patient Management Routes
|--------------------------------------------------------------------------
| Admin-only routes to manage patient-related data.
*/
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('patient-list', [PatientApiController::class, 'patientList']);
    Route::get('patient-details/{id}', [PatientApiController::class, 'doctorDetails']);
});

/*
|--------------------------------------------------------------------------
| Doctor Routes
|--------------------------------------------------------------------------
| Routes available to authenticated doctors for managing their profile,
| medical information, and financial data.
*/
Route::prefix('doctor')->middleware(['doctor', 'auth:sanctum'])->group(function () {
    // Profile creation and verification
    Route::post('create-profile', [DoctorUserApiController::class, 'createProfile']);
    Route::post('medical-info-verify', [DoctorUserApiController::class, 'medicalInfoVerify']);
    Route::get('profile/verification-status', [DoctorUserApiController::class, 'checkVerificationStatus']);

    // Profile and data updates
    Route::get('profile/details', [DoctorProfileApiController::class, 'profileDetails']);
    Route::post('profile/update', [DoctorProfileApiController::class, 'updateProfileDetails']);
    Route::post('medical/update', [DoctorProfileApiController::class, 'medicalDataUpdate']);
    Route::post('financial/update', [DoctorProfileApiController::class, 'financialUpdate']);

    //coupon route
    Route::get('/coupons',[CouponApiController::class, 'index']);
    Route::post('/coupons/create',[CouponApiController::class, 'store']);
    Route::post('/coupons/update/{id}',[CouponApiController::class, 'update']);
    Route::delete('/coupons/delete/{id}',[CouponApiController::class, 'destroy']);

});

/*
|--------------------------------------------------------------------------
| Patient Routes
|--------------------------------------------------------------------------
| Routes available to authenticated patients for managing their profile,
| members, and booking consultations.
*/
Route::prefix('patient')->middleware(['patient', 'auth:sanctum'])->group(function () {
    // Profile
    Route::post('create-profile', [PatientProfileApiController::class, 'createProfile']);
    Route::get('profile/details', [PatientProfileApiController::class, 'profileDetails']);
    Route::post('update/profile/details', [PatientProfileApiController::class, 'updateProfileDetails']);

    // Account & member management
    Route::get('accounts', [PatientProfileApiController::class, 'accountDetails']);
    Route::post('add/member/account', [PatientProfileApiController::class, 'addMemberAccount']);
    Route::post('update/member/account/{id}', [PatientProfileApiController::class, 'updateMemberAccount']);
    Route::delete('delete/member/account/{id}', [PatientProfileApiController::class, 'deleteMemberAccount']);

    // Consultation booking and Stripe webhook
    Route::post('/consultations', [ConsultationBookingController::class, 'book']);
    Route::get('/payment/success',[ConsultationBookingController::class,'success'])->name('payment.success');
    Route::get('/payment/fail',[ConsultationBookingController::class,'fail'])->name('payment.fail');
    Route::post('/stripe/webhook', [ConsultationBookingController::class, 'handleWebhook'])->middleware('stripe.signature');
});

/*
|--------------------------------------------------------------------------
| Patient Medical Record Routes
|--------------------------------------------------------------------------
| Routes to manage personal and family member medical reports.
| Accessible by authenticated patients.
*/
Route::middleware('auth:sanctum')->prefix('patient/medical-record')->group(function () {
    Route::get('/', [MedicalApiRecordController::class, 'index']);
    Route::post('/store', [MedicalApiRecordController::class, 'storeMedicalRecord']);
    Route::post('/update/{id}', [MedicalApiRecordController::class, 'updateMedicalRecord']);
    Route::delete('/delete/{id}', [MedicalApiRecordController::class, 'destroyMedicalRecord']);
});
Route::get('/php-info', fn() => phpinfo());
