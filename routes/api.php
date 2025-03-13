<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\StaffProfileController;
use App\Http\Controllers\Admin\AdminApprovalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail']);
Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);

// Protected Routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Auth logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // User Routes
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::patch('/users/{id}', [UserController::class, 'update']);

    // Booking Routes
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::patch('/bookings/{id}', [BookingController::class, 'update']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/complete', [BookingController::class, 'complete']);
    Route::post('/bookings/{id}/rate', [BookingController::class, 'rate']);
    Route::get('/bookings/{id}/tracking', [BookingController::class, 'tracking']);

    // TODO: Payment Routes
    // Route::post('/payments/initialize', [PaymentController::class, 'initialize']);
    // Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
    // Route::post('/payments/refund', [PaymentController::class, 'refund']);
    // Route::get('/payments/history', [PaymentController::class, 'history']);
    // Route::get('/payments/{id}/status', [PaymentController::class, 'status']);

    // Provider Services Management
    Route::get('/provider/services', [ProviderServiceController::class, 'index']);
    Route::post('/provider/services', [ProviderServiceController::class, 'store']);
    Route::get('/provider/services/{id}', [ProviderServiceController::class, 'show']);
    Route::put('/provider/services/{id}', [ProviderServiceController::class, 'update']);
    Route::delete('/provider/services/{id}', [ProviderServiceController::class, 'destroy']);

    // Staff Profiles Management
    Route::get('/provider/staff', [StaffProfileController::class, 'index']);
    Route::post('/provider/staff', [StaffProfileController::class, 'store']);
    Route::get('/provider/staff/{id}', [StaffProfileController::class, 'show']);
    Route::put('/provider/staff/{id}', [StaffProfileController::class, 'update']);
    Route::delete('/provider/staff/{id}', [StaffProfileController::class, 'destroy']);

    // Admin Approval Routes
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/pending-services', [AdminApprovalController::class, 'getPendingServices']);
        Route::get('/pending-staff', [AdminApprovalController::class, 'getPendingStaffProfiles']);
        Route::post('/approve-service/{id}', [AdminApprovalController::class, 'approveService']);
        Route::post('/approve-staff/{id}', [AdminApprovalController::class, 'approveStaffProfile']);
    });
});

// Public Service Routes
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/services/{id}/providers', [ServiceController::class, 'providers']);
Route::get('/services/{id}/availability', [ServiceController::class, 'availability']);
Route::get('/services/{id}/pricing', [ServiceController::class, 'pricing']);
Route::post('/services/{id}/calculate-price', [ServiceController::class, 'calculatePrice']);
