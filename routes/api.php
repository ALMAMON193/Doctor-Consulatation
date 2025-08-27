<?php

use App\Http\Controllers\API\APP\Doctor\ConsultationController as DoctorConsultationController;
use App\Http\Controllers\API\APP\Doctor\NotificationController as DoctorNotificationController;
use App\Http\Controllers\API\APP\Doctor\PatientHistoryController;
use App\Http\Controllers\API\APP\Doctor\ProfileApiController as DoctorProfileApiController;
use App\Http\Controllers\API\APP\Doctor\UserApiController as DoctorUserApiController;
use App\Http\Controllers\API\APP\Doctor\WalletAPIController;
use App\Http\Controllers\API\APP\Patient\ConsultationBookingController;
use App\Http\Controllers\API\APP\Patient\ConsultationChatApiController;
use App\Http\Controllers\API\APP\Patient\ConsultationRecordApiController;
use App\Http\Controllers\API\APP\Patient\HomeApiController;
use App\Http\Controllers\API\APP\Patient\MedicalApiRecordController;
use App\Http\Controllers\API\APP\Patient\NotificationAPIController as PatientNotificationController;
use App\Http\Controllers\API\APP\Patient\ProfileApiController as PatientProfileApiController;
use App\Http\Controllers\API\APP\Patient\RatingApiController;
use App\Http\Controllers\API\Auth\AuthApiController;
use App\Http\Controllers\API\WEB\Dashboard\Consultation\ConsultationApiController;
use App\Http\Controllers\API\WEB\Dashboard\Coupon\CouponController;
use App\Http\Controllers\API\WEB\Dashboard\Doctor\DoctorApiController;
use App\Http\Controllers\API\WEB\Dashboard\Patient\PatientApiController;
use App\Http\Controllers\API\WEB\Dashboard\Specialization\SpecializationController;
use App\Http\Controllers\API\WEB\Dashboard\WithDrawRequest\TransactionController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// ✅ Broadcasting Auth Route
Broadcast::routes(['middleware' => ['api', 'auth:sanctum']]);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login',           [AuthApiController::class, 'loginApi']);
    Route::post('register',        [AuthApiController::class, 'registerApi']);
    Route::post('verify-email',    [AuthApiController::class, 'verifyEmailApi']);
    Route::post('forgot-password', [AuthApiController::class, 'forgotPasswordApi']);
    Route::post('reset-password',  [AuthApiController::class, 'resetPasswordApi']);
    Route::post('resend-otp',      [AuthApiController::class, 'resendOtpApi']);
    Route::post('verify-otp',      [AuthApiController::class, 'verifyOtpApi']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthApiController::class, 'logoutApi']);
});

/*
|--------------------------------------------------------------------------
| Admin Panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('admin')->group(function () {

    // Doctors
    Route::get('doctor-list',               [DoctorApiController::class, 'doctorList']);
    Route::get('doctor-details/{id}',       [DoctorApiController::class, 'doctorDetails']);
    Route::post('create-doctor',            [DoctorApiController::class, 'createDoctor']);

    // Patients
    Route::get('patient-list',              [PatientApiController::class, 'patientList']);
    Route::get('patient-details/{id}',      [PatientApiController::class, 'patientDetails']);
    Route::post('create-patient',           [PatientApiController::class, 'createPatient']);

    // Consultations
    Route::get('consultation-list',         [ConsultationApiController::class, 'consultationList']);
    Route::get('consultation-details/{id}', [ConsultationApiController::class, 'consultationDetails']);
    Route::post('consultation-create',      [ConsultationApiController::class, 'consultationCreate']);

    // Coupons
    Route::get('coupons',                   [CouponController::class, 'index']);
    Route::post('coupon-create',            [CouponController::class, 'store']);
    Route::get('coupon-details/{id}',       [CouponController::class, 'show']);

    //withdraw request
    Route::get('withdraw-requests',         [TransactionController::class, 'withdrawRequests']);
    Route::post('withdraw-accept',           [TransactionController::class, 'acceptRequest']);
    Route::post('withdraw-reject',           [TransactionController::class, 'rejectRequest']);

    // Specialization route
    Route::get('/specializations', [SpecializationController::class, 'index']);
    Route::post('/specializations', [SpecializationController::class, 'store']);
    Route::post('/specializations/{specialization}', [SpecializationController::class, 'update']);
    Route::delete('/specializations/{specialization}', [SpecializationController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Doctor Panel
|--------------------------------------------------------------------------
*/
Route::prefix('doctor')->middleware(['doctor', 'auth:sanctum'])->group(function () {

    // Profile
    Route::post('create-profile',             [DoctorUserApiController::class, 'createProfile']);
    Route::post('medical-info-verify',        [DoctorUserApiController::class, 'medicalInfoVerify']);
    Route::get('profile/verification-status', [DoctorUserApiController::class, 'checkVerificationStatus']);

    Route::get('profile/details',             [DoctorProfileApiController::class, 'profileDetails']);
    Route::get('medical/details',             [DoctorProfileApiController::class, 'medicalDetails']);
    Route::get('financial/details',           [DoctorProfileApiController::class, 'financialDetails']);

    Route::post('profile/update',             [DoctorProfileApiController::class, 'updateProfileDetails']);
    Route::post('medical/update',             [DoctorProfileApiController::class, 'medicalDataUpdate']);
    Route::post('financial/update',           [DoctorProfileApiController::class, 'financialUpdate']);

    // Consultations
    Route::get('available/consultation',      [DoctorProfileApiController::class, 'activeConsultation']);
    Route::get('consultation/details/{id}',   [DoctorProfileApiController::class, 'patientDetails']);
    Route::get('consultations/{id}',          [DoctorConsultationController::class, 'show']);
    Route::post('consultations/{id}/accept',  [DoctorConsultationController::class, 'accept']);

    // Patients
    Route::get('patient/history',             [PatientHistoryController::class, 'patientHistory']);

    // Notifications
    Route::get('notifications',               [DoctorNotificationController::class, 'index']);
    Route::post('notifications/{id}/read',    [DoctorNotificationController::class, 'markAsRead']);

    // Wallet
    Route::get('my-wallet',                   [WalletAPIController::class, 'wallet']);
    Route::post('withdraw-request',           [WalletAPIController::class, 'requestWithdraw']);
    Route::get('transaction-history',         [WalletAPIController::class, 'viewTransactionHistory']);
});

/*
|--------------------------------------------------------------------------
| Patient Panel
|--------------------------------------------------------------------------
*/
Route::prefix('patient')->middleware(['patient', 'auth:sanctum'])->group(function () {

    // Home
    Route::get('/home',                             [HomeApiController::class, 'index']);
    Route::post('check-coupon',                     [ConsultationBookingController::class, 'checkCoupon']);

    // Profile
    Route::post('create-profile',                   [PatientProfileApiController::class, 'createProfile']);
    Route::get('profile/details',                   [PatientProfileApiController::class, 'profileDetails']);
    Route::post('update/profile/details',           [PatientProfileApiController::class, 'updateProfileDetails']);

    // Members
    Route::get('accounts',                          [PatientProfileApiController::class, 'accountDetails']);
    Route::post('add/member/account',               [PatientProfileApiController::class, 'addMemberAccount']);
    Route::post('update/member/account/{id}',       [PatientProfileApiController::class, 'updateMemberAccount']);
    Route::delete('delete/member/account/{id}',     [PatientProfileApiController::class, 'deleteMemberAccount']);

    // Consultations
    Route::post('consultations',                    [ConsultationBookingController::class, 'book']);
    Route::get('consultation-details',              [ConsultationRecordApiController::class, 'index']);
    Route::delete('consultations/delete/{id}',      [ConsultationRecordApiController::class, 'destroy']);

    // Ratings
    Route::post('consultation-ratting',             [RatingApiController::class, 'store']);

    // Notifications
    Route::get('notifications',                     [PatientNotificationController::class, 'index']);
    Route::post('notifications/{id}/read',          [PatientNotificationController::class, 'markAsRead']);

    //medical record
    Route::get('medical-record',                    [MedicalApiRecordController::class, 'index']);
    Route::post('medical-record/store',             [MedicalApiRecordController::class, 'storeMedicalRecord']);
    Route::post('medical-record/update/{id}',       [MedicalApiRecordController::class, 'updateMedicalRecord']);
    Route::delete('medical-record/delete/{id}',     [MedicalApiRecordController::class, 'destroyMedicalRecord']);
});

/*
|--------------------------------------------------------------------------
| Chat
|--------------------------------------------------------------------------
*/
Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
    Route::post('send-message', [ConsultationChatApiController::class, 'sendMessage']);
    Route::get('history',       [ConsultationChatApiController::class, 'getMessageHistory']);
});

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
*/
Route::get('payment/success',    [ConsultationBookingController::class, 'success'])->name('payment.success');
Route::get('payment/fail',       [ConsultationBookingController::class, 'fail'])->name('payment.fail');
Route::post('stripe/webhook',    [ConsultationBookingController::class, 'handleWebhook'])->middleware('stripe.signature');
Route::get('stripe/publish-key', [ConsultationBookingController::class, 'publishKey']);

/*
|--------------------------------------------------------------------------
| Miscellaneous
|--------------------------------------------------------------------------
*/
Route::get('specializations', [DoctorUserApiController::class, 'specializations']);
