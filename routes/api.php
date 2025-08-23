<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Controllers
use App\Http\Controllers\API\Auth\AuthApiController;
use App\Http\Controllers\API\Dashboard\Consultation\ConsultationApiController;
use App\Http\Controllers\API\Dashboard\Doctor\DoctorApiController;
use App\Http\Controllers\API\Dashboard\Patient\PatientApiController;
use App\Http\Controllers\API\Dashboard\Coupon\CouponController;
use App\Http\Controllers\API\Doctor\UserApiController              as DoctorUserApiController;
use App\Http\Controllers\API\Doctor\ProfileApiController           as DoctorProfileApiController;
use App\Http\Controllers\API\Doctor\NotificationController         as DoctorNotificationController;
use App\Http\Controllers\API\Doctor\ConsultationController         as DoctorConsultationController;
use App\Http\Controllers\API\Doctor\PatientHistoryController;
use App\Http\Controllers\API\Patient\ProfileApiController          as PatientProfileApiController;
use App\Http\Controllers\API\Patient\ConsultationBookingController;
use App\Http\Controllers\API\Patient\ConsultationChatApiController;
use App\Http\Controllers\API\Patient\ConsultationRecordApiController;
use App\Http\Controllers\API\Patient\MedicalApiRecordController;
use App\Http\Controllers\API\Patient\NotificationAPIController     as PatientNotificationController;
use App\Http\Controllers\API\Patient\HomeApiController;
use App\Http\Controllers\API\Patient\RatingApiController;


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login',           [AuthApiController::class, 'loginApi']);           // User login
    Route::post('register',        [AuthApiController::class, 'registerApi']);        // User registration
    Route::post('verify-email',    [AuthApiController::class, 'verifyEmailApi']);     // Verify email
    Route::post('forgot-password', [AuthApiController::class, 'forgotPasswordApi']);  // Forgot password
    Route::post('reset-password',  [AuthApiController::class, 'resetPasswordApi']);   // Reset password
    Route::post('resend-otp',      [AuthApiController::class, 'resendOtpApi']);       // Resend OTP
    Route::post('verify-otp',      [AuthApiController::class, 'verifyOtpApi']);       // Verify OTP
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout',     [AuthApiController::class, 'logoutApi']);          // User logout
});


/*
|--------------------------------------------------------------------------
| Admin Panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('admin')->group(function () {

    // Doctors
    Route::get('doctor-list',          [DoctorApiController::class, 'doctorList']);       // List doctors
    Route::get('doctor-details/{id}',  [DoctorApiController::class, 'doctorDetails']);    // Doctor details
    Route::post('create-doctor',       [DoctorApiController::class, 'createDoctor']);     // Create doctor

    // Patients
    Route::get('patient-list',         [PatientApiController::class, 'patientList']);     // List patients
    Route::get('patient-details/{id}', [PatientApiController::class, 'patientDetails']);  // Patient details
    Route::post('create-patient',      [PatientApiController::class, 'createPatient']);   // Create patient

    // Consultations
    Route::get('consultation-list',        [ConsultationApiController::class, 'consultationList']);   // List consultations
    Route::get('consultation-details/{id}',[ConsultationApiController::class, 'consultationDetails']); // Consultation details
    Route::post('consultation-create',     [ConsultationApiController::class, 'consultationCreate']);  // Create consultation

    // Coupons
    Route::get('coupons',              [CouponController::class, 'index']);              // List coupons
    Route::post('coupon-create',       [CouponController::class, 'store']);              // Create coupon
    Route::get('coupon-details/{id}',  [CouponController::class, 'show']);               // Coupon details
});

/*
|--------------------------------------------------------------------------
| Doctor Panel
|--------------------------------------------------------------------------
*/
Route::prefix('doctor')->middleware(['doctor', 'auth:sanctum'])->group(function () {

    // Profile
    Route::post('create-profile',      [DoctorUserApiController::class, 'createProfile']);       // Create profile
    Route::post('medical-info-verify', [DoctorUserApiController::class, 'medicalInfoVerify']);   // Verify medical info
    Route::get('profile/verification-status', [DoctorUserApiController::class, 'checkVerificationStatus']); // Verification status

    Route::get('profile/details',      [DoctorProfileApiController::class, 'profileDetails']);   // Profile details
    Route::get('medical/details',      [DoctorProfileApiController::class, 'medicalDetails']);   // Medical details
    Route::get('financial/details',    [DoctorProfileApiController::class, 'financialDetails']); // Financial details

    Route::post('profile/update',      [DoctorProfileApiController::class, 'updateProfileDetails']); // Update profile
    Route::post('medical/update',      [DoctorProfileApiController::class, 'medicalDataUpdate']);    // Update medical info
    Route::post('financial/update',    [DoctorProfileApiController::class, 'financialUpdate']);      // Update financial info

    // Consultations
    Route::get('available/consultation',   [DoctorProfileApiController::class, 'activeConsultation']);  // Available consultations
    Route::get('consultation/details/{id}',[DoctorProfileApiController::class, 'patientDetails']);      // Patient details
    Route::get('consultations/{id}',       [DoctorConsultationController::class, 'show']);              // View consultation
    Route::post('consultations/{id}/accept',[DoctorConsultationController::class, 'accept']);           // Accept consultation

    // Patients
    Route::get('patient/history',      [PatientHistoryController::class, 'patientHistory']);     // Patient history

    // Notifications
    Route::get('notifications',        [DoctorNotificationController::class, 'index']);          // List notifications
    Route::post('notifications/{id}/read',[DoctorNotificationController::class, 'markAsRead']);  // Mark as read
});

/*
|--------------------------------------------------------------------------
| Patient Panel
|--------------------------------------------------------------------------
*/
Route::prefix('patient')->middleware(['patient', 'auth:sanctum'])->group(function () {

    //Home
    Route::get('/home', [HomeApiController::class, 'index']);   // Fetch Home Data

    // Profile
    Route::post('create-profile',          [PatientProfileApiController::class, 'createProfile']);     // Create profile
    Route::get('profile/details',          [PatientProfileApiController::class, 'profileDetails']);    // Profile details
    Route::post('update/profile/details',  [PatientProfileApiController::class, 'updateProfileDetails']); // Update profile

    // Members
    Route::get('accounts',                 [PatientProfileApiController::class, 'accountDetails']);    // List member accounts
    Route::post('add/member/account',      [PatientProfileApiController::class, 'addMemberAccount']);  // Add member
    Route::post('update/member/account/{id}',[PatientProfileApiController::class, 'updateMemberAccount']); // Update member
    Route::delete('delete/member/account/{id}',[PatientProfileApiController::class, 'deleteMemberAccount']); // Delete member

    // Consultations
    Route::post('consultations',           [ConsultationBookingController::class, 'book']);            // Book consultation
    Route::get('consultation-details',     [ConsultationRecordApiController::class, 'index']);         // Consultation list
    Route::delete('consultations/delete/{id}',[ConsultationRecordApiController::class, 'destroy']);    // Delete consultation

    // Ratings
    Route::post('consultation-ratting',    [RatingApiController::class, 'store']);                     // Submit rating

    // Notifications
    Route::get('notifications',            [PatientNotificationController::class, 'index']);           // List notifications
    Route::post('notifications/{id}/read', [PatientNotificationController::class, 'markAsRead']);      // Mark as read
});

/*
|--------------------------------------------------------------------------
| Chat
|--------------------------------------------------------------------------
*/
Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
    Route::get('participants', [ConsultationChatApiController::class, 'getChatParticipantsInfo']); // Chat participants
    Route::post('send-message', [ConsultationChatApiController::class, 'sendMessage']);             // Send message
    Route::get('history',       [ConsultationChatApiController::class, 'getConversationHistory']);  // Chat history
});


/*
|--------------------------------------------------------------------------
| Medical Records
|--------------------------------------------------------------------------
*/
Route::prefix('patient/medical-record')->middleware(['patient', 'auth:sanctum'])->group(function () {
    Route::get('/',           [MedicalApiRecordController::class, 'index']);              // List medical records
    Route::post('store',      [MedicalApiRecordController::class, 'storeMedicalRecord']); // Add medical record
    Route::post('update/{id}',[MedicalApiRecordController::class, 'updateMedicalRecord']); // Update medical record
    Route::delete('delete/{id}',[MedicalApiRecordController::class, 'destroyMedicalRecord']); // Delete medical record
});
/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
*/
Route::get('payment/success', [ConsultationBookingController::class, 'success'])->name('payment.success'); // Payment success
Route::get('payment/fail',    [ConsultationBookingController::class, 'fail'])->name('payment.fail');       // Payment fail
Route::post('stripe/webhook', [ConsultationBookingController::class, 'handleWebhook'])->middleware('stripe.signature'); // Stripe webhook
Route::get('stripe/publish-key',[ConsultationBookingController::class, 'publishKey']);    //published key in stripe
Route::get('check-coupon',[ConsultationBookingController::class, 'checkCoupon']);    //published key in stripe

/*
|--------------------------------------------------------------------------
| Miscellaneous
|--------------------------------------------------------------------------
*/
Route::get('specializations', [DoctorUserApiController::class, 'specializations']);  // List all specializations

// WebSocket Broadcast (Laravel Echo / Pusher)
Broadcast::routes(['middleware' => ['auth:sanctum']]);
