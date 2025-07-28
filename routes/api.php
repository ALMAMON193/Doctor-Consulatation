<?php

use App\Http\Controllers\API\Dashboard\Consultation\ConsultationApiController;
use App\Http\Controllers\API\Doctor\PatientHistoryController;
use App\Http\Controllers\API\Patient\HomeApiController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthApiController;
use App\Http\Controllers\API\Doctor\CouponApiController;
use App\Http\Controllers\API\Doctor\UserApiController as DoctorUserApiController;
use App\Http\Controllers\API\Doctor\ProfileApiController as DoctorProfileApiController;
use App\Http\Controllers\API\Dashboard\Doctor\DoctorApiController;
use App\Http\Controllers\API\Dashboard\Patient\PatientApiController;
use App\Http\Controllers\API\Patient\ProfileApiController as PatientProfileApiController;
use App\Http\Controllers\API\Patient\ConsultationBookingController;
use App\Http\Controllers\API\Patient\ConsultationChatApiController;
use App\Http\Controllers\API\Patient\ConsultationRecordApiController;
use App\Http\Controllers\API\Patient\MedicalApiRecordController;
use App\Http\Controllers\API\Patient\RatingApiController;

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthApiController::class, 'loginApi']); // User login
    Route::post('register', [AuthApiController::class, 'registerApi']); // User registration
    Route::post('verify-email', [AuthApiController::class, 'verifyEmailApi']); // Verify email
    Route::post('forgot-password', [AuthApiController::class, 'forgotPasswordApi']); // Forgot password
    Route::post('reset-password', [AuthApiController::class, 'resetPasswordApi']); // Reset password
    Route::post('resend-otp', [AuthApiController::class, 'resendOtpApi']); // Resend OTP
    Route::post('verify-otp', [AuthApiController::class, 'verifyOtpApi']); // Verify OTP
});

// Authenticated user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthApiController::class, 'logoutApi']); // User logout
});

// Admin doctor management routes
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('doctor-list', [DoctorApiController::class, 'doctorList']); // List doctors
    Route::get('doctor-details/{id}', [DoctorApiController::class, 'doctorDetails']); // Doctor details
    Route::post('create-doctor', [DoctorApiController::class, 'createDoctor']);
});

// Admin patient management routes
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('patient-list', [PatientApiController::class, 'patientList']); // List patients
    Route::get('patient-details/{id}', [PatientApiController::class, 'patientDetails']); // Patient details
    Route::post('create-patient', [PatientApiController::class, 'createPatient']);
});

// Admin consultation management routes
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('consultation-list', [ConsultationApiController::class, 'consultationList']); // List Consultations
    Route::get('consultation-details/{id}', [ConsultationApiController::class, 'consultationDetails']); // Consultation details
    Route::post('consultation-create', [ConsultationApiController::class, 'consultationCreate']);
});

// Doctor routes (profile, medical, financial, coupons)
Route::prefix('doctor')->middleware(['doctor', 'auth:sanctum'])->group(function () {
    Route::post('create-profile', [DoctorUserApiController::class, 'createProfile']); // Create profile
    Route::post('medical-info-verify', [DoctorUserApiController::class, 'medicalInfoVerify']); // Verify medical info
    Route::get('profile/verification-status', [DoctorUserApiController::class, 'checkVerificationStatus']); // Check verification

    Route::get('profile/details', [DoctorProfileApiController::class, 'profileDetails']); // Get profile
    Route::get('medical/details', [DoctorProfileApiController::class, 'medicalDetails']); // Get profile
    Route::get('financial/details', [DoctorProfileApiController::class, 'financialDetails']); // Get profile

    Route::post('profile/update', [DoctorProfileApiController::class, 'updateProfileDetails']); // Update profile
    Route::post('medical/update', [DoctorProfileApiController::class, 'medicalDataUpdate']); // Update medical info
    Route::post('financial/update', [DoctorProfileApiController::class, 'financialUpdate']); // Update financial info

    Route::get('coupons', [CouponApiController::class, 'index']); // List coupons
    Route::post('coupons/create', [CouponApiController::class, 'store']); // Create coupon
    Route::post('coupons/update/{id}', [CouponApiController::class, 'update']); // Update coupon
    Route::delete('coupons/delete/{id}', [CouponApiController::class, 'destroy']); // Delete coupon
    //available consultation for admin or patient member
    Route::get('available/consultation',[DoctorProfileApiController::class,'activeConsultation']);   //available consultation for admin or patient member
    Route::get('consultation/details/{id}',[DoctorProfileApiController::class,'patientDetails']);   //Patient Details

    //patient History
    Route::get('patient/history',[PatientHistoryController::class,'patientHistory']);   //Patient History

});

// Patient routes (profile, members, consultations, ratings)
Route::prefix('patient')->middleware(['patient', 'auth:sanctum'])->group(function () {
    Route::post('create-profile', [PatientProfileApiController::class, 'createProfile']); // Create profile
    Route::get('profile/details', [PatientProfileApiController::class, 'profileDetails']); // Get profile
    Route::post('update/profile/details', [PatientProfileApiController::class, 'updateProfileDetails']); // Update profile

    Route::get('accounts', [PatientProfileApiController::class, 'accountDetails']); // Get accounts
    Route::post('add/member/account', [PatientProfileApiController::class, 'addMemberAccount']); // Add member
    Route::post('update/member/account/{id}', [PatientProfileApiController::class, 'updateMemberAccount']); // Update member
    Route::delete('delete/member/account/{id}', [PatientProfileApiController::class, 'deleteMemberAccount']); // Delete member

    Route::post('consultation-ratting', [RatingApiController::class, 'store']); // Submit rating
    Route::post('consultations', [ConsultationBookingController::class, 'book']); // Book consultation
    Route::get('consultation-details', [ConsultationRecordApiController::class, 'index']); // Consultation list
    Route::delete('consultations/delete/{id}', [ConsultationRecordApiController::class, 'destroy']); // Delete consultation
});

// Payment routes
Route::get('payment/success', [ConsultationBookingController::class, 'success'])->name('payment.success'); // Payment success
Route::get('payment/fail', [ConsultationBookingController::class, 'fail'])->name('payment.fail'); // Payment failure
Route::post('stripe/webhook', [ConsultationBookingController::class, 'handleWebhook'])->middleware('stripe.signature'); // Stripe webhook

// Chat routes (doctor <-> patient communication)
Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
    Route::get('participants', [ConsultationChatApiController::class, 'getChatParticipantsInfo']); // Chat participants
    Route::post('send-message', [ConsultationChatApiController::class, 'sendMessage']); // Send message
    Route::get('history', [ConsultationChatApiController::class, 'getConversationHistory']);
});

// Medical records (patient and member reports)
Route::prefix('patient/medical-record')->middleware('patient', 'auth:sanctum')->group(function () {
    Route::get('/', [MedicalApiRecordController::class, 'index']); // List medical records
    Route::post('store', [MedicalApiRecordController::class, 'storeMedicalRecord']); // Add medical record
    Route::post('update/{id}', [MedicalApiRecordController::class, 'updateMedicalRecord']); // Update medical record
    Route::delete('delete/{id}', [MedicalApiRecordController::class, 'destroyMedicalRecord']); // Delete medical record
});
// All Specializations
Route::get('specializations',[DoctorUserApiController::class,'specializations']);   //all specializations
//patient home records
Route::prefix('patient/home')->middleware(['patient', 'auth:sanctum'])->group(function () {
    Route::get('/', [HomeApiController::class, 'index']);
});
Broadcast::routes(['middleware' => ['auth:sanctum']]);
