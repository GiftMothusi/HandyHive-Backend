<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    /**
     * Handle user registration
     *
     * @throws ValidationException
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Start a database transaction to ensure both user and provider are created or neither
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'userType' => $request->userType,
                'phone' => $request->phone,
                'status' => 'active',
                'email_verified_at' => null,
            ]);
            
            // If user type is provider, create a service provider record
            if ($request->userType === 'provider') {
                try {
                    \App\Models\ServiceProvider::create([
                        'user_id' => $user->id,
                        'description' => 'Service provider',
                        'category' => $user->name . "'s Services",
                        'status' => 'active',
                    ]);
                } catch (\Throwable $providerError) {
                    // Log the specific provider creation error
                    Log::error('Failed to create service provider record', [
                        'user_id' => $user->id,
                        'error' => $providerError->getMessage(),
                        'trace' => $providerError->getTraceAsString()
                    ]);
                    
                    // We'll still continue as the user was created successfully
                }
            }

            // Wrap email verification in try-catch to prevent registration failure
            try {
                event(new Registered($user));
            } catch (Throwable $emailError) {
                // Log email error but continue with registration
                Log::warning('Email verification could not be sent', [
                    'user_id' => $user->id,
                    'error' => $emailError->getMessage()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Commit the transaction as everything succeeded
            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'userType' => $user->userType,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'email_verified' => (bool) $user->email_verified_at,
                ],
                'token' => $token
            ], 201);
        } catch (Throwable $e) {
            // Rollback the transaction if anything failed
            \Illuminate\Support\Facades\DB::rollBack();
            
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's a database integrity error (likely duplicate email)
            if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                return response()->json([
                    'message' => 'Registration failed. Email already exists.',
                    'errors' => ['email' => ['This email is already registered']]
                ], 422);
            }

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'errors' => ['general' => ['An unexpected error occurred during registration. Please try again.']]
            ], 500);
        }
    }

    /**
     * Handle user login
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $request->authenticate();

            /** @var User $user */
            $user = Auth::user();

            // Check if user is active
            if ($user->status !== 'active') {
                throw ValidationException::withMessages([
                    'email' => ['Your account is not active. Please contact support.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Logged in successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'userType' => $user->userType,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'email_verified' => (bool) $user->email_verified_at,
                ],
                'token' => $token
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Login failed. Please try again.',
                'errors' => ['email' => ['Unable to authenticate']]
            ], 500);
        }
    }

    /**
     * Handle forgot password request
     *
     * @throws ValidationException
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['message' => __($status)]);
            }

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Password reset request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Unable to process password reset request.',
                'errors' => ['email' => ['Service temporarily unavailable']]
            ], 500);
        }
    }

    /**
     * Handle password reset
     *
     * @throws ValidationException
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json(['message' => __($status)]);
            }

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Unable to reset password.',
                'errors' => ['email' => ['Service temporarily unavailable']]
            ], 500);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user) {
                /** @var PersonalAccessToken|null $token */
                $token = $user->currentAccessToken();
                if ($token instanceof PersonalAccessToken) {
                    $token->delete();
                }
            }

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (Throwable $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Logout failed. Please try again.'
            ], 500);
        }
    }
    /**
     * Verify email address
     */
    public function verifyEmail(string $id, string $hash): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid verification link'],
                ]);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email already verified']);
            }

            $user->markEmailAsVerified();

            return response()->json(['message' => 'Email verified successfully']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Unable to verify email.',
                'errors' => ['email' => ['Service temporarily unavailable']]
            ], 500);
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if ($user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email already verified']);
            }

            $user->sendEmailVerificationNotification();

            return response()->json(['message' => 'Verification link sent']);
        } catch (Throwable $e) {
            Log::error('Resend verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Unable to send verification email.',
                'errors' => ['email' => ['Service temporarily unavailable']]
            ], 500);
        }
    }
}
