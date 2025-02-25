<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 6.1 Authentication
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/auth/verify-phone', [AuthController::class, 'verifyPhone']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// 6.2 Users
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::get('/users/{id}/ratings', [UserController::class, 'ratings']);
    Route::post('/users/{id}/verify', [UserController::class, 'verify']);
    Route::get('/users/{id}/documents', [UserController::class, 'documents']);
    Route::post('/users/{id}/documents', [UserController::class, 'uploadDocuments']);
    Route::get('/users/{id}/availability', [UserController::class, 'availability']);
    Route::patch('/users/{id}/availability', [UserController::class, 'updateAvailability']);
});

// 6.3 Services
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::get('/services/{id}/providers', [ServiceController::class, 'providers']);
Route::get('/services/{id}/availability', [ServiceController::class, 'availability']);
Route::get('/services/{id}/pricing', [ServiceController::class, 'pricing']);
Route::post('/services/{id}/calculate-price', [ServiceController::class, 'calculatePrice']);

// 6.4 Bookings
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::patch('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/complete', [BookingController::class, 'complete']);
    Route::post('/bookings/{id}/rate', [BookingController::class, 'rate']);
    Route::get('/bookings/{id}/tracking', [BookingController::class, 'tracking']);
});

// 6.5 Payments
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/payments/initialize', [PaymentController::class, 'initialize']);
    Route::post('/payments/confirm', [PaymentController::class, 'confirm']);
    Route::post('/payments/refund', [PaymentController::class, 'refund']);
    Route::get('/payments/history', [PaymentController::class, 'history']);
    Route::get('/payments/{id}/status', [PaymentController::class, 'status']);
});

// User Profile Route (from original file)
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
