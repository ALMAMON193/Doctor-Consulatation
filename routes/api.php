<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthApiController;
use App\Http\Controllers\API\Doctor\UserApiController;
use App\Http\Controllers\API\Doctor\ProfileApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**===========================Auth API Start================================= */

Route::prefix('auth')->group(function () {

    Route::post('login', [AuthApiController::class, 'loginApi']);
    Route::post('register', [AuthApiController::class, 'registerApi']);
    Route::post('verify-email', [AuthApiController::class, 'verifyEmailApi']);
    Route::post('forgot-password', [AuthApiController::class, 'forgotPasswordApi']);
    Route::post('reset-password', [AuthApiController::class, 'resetPasswordApi']);
    Route::post('resend-otp', [AuthApiController::class, 'resendOtpApi']);
    Route::post('verify-otp', [AuthApiController::class, 'verifyOtpApi']);
});

/**===========================Auth API End================================= */

/**===========================Doctor API Start================================= */

Route::controller(UserApiController::class)->prefix('doctor')->middleware('auth:sanctum')->group(function () {
    Route::post('create-profile', 'createProfile');
    Route::post('medical-info-verify', 'medicalInfoVerify');
    Route::get('profile/verification-status', 'checkVerificationStatus');
});
Route::middleware('auth:sanctum')->prefix('doctor')->group(function () {
    Route::get('profile/details',[ProfileApiController::class, 'profileDetails']);
    Route::post('profile/update', [ProfileApiController::class, 'updateProfileDetails']);
    Route::post('medical/update', [ProfileApiController::class, 'medicalDataUpdate']);
    Route::post('financial/update', [ProfileApiController::class, 'financialUpdate']);
});

/**===========================Doctor API End================================= */
