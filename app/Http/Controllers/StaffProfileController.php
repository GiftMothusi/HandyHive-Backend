<?php

namespace App\Http\Controllers;

use App\Models\StaffProfile;
use App\Models\ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StaffProfileController extends Controller
{
    /**
     * Display a listing of the staff profiles.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is a service provider directly from the users table
            if ($user->user_type !== 'provider') {
                return response()->json([
                    'message' => 'You are not registered as a service provider',
                    'errors' => ['provider' => ['User is not a service provider']]
                ], 403);
            }

            // Get staff profiles directly using the user_id instead of provider_id
            $staffProfiles = StaffProfile::where('provider_id', $user->id)->get();

            return response()->json([
                'message' => 'Staff profiles retrieved successfully',
                'data' => $staffProfiles
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve staff profiles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve staff profiles',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Store a newly created staff profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is a service provider directly from the users table
            if ($user->user_type !== 'provider') {
                return response()->json([
                    'message' => 'You are not registered as a service provider',
                    'errors' => ['provider' => ['User is not a service provider']]
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'bio' => 'required|string',
                'skills' => 'required|array',
                'skills.*' => 'string',
                'profile_image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $staffProfile = new StaffProfile();
            $staffProfile->provider_id = $provider->id;
            $staffProfile->name = $request->name;
            $staffProfile->position = $request->position;
            $staffProfile->bio = $request->bio;
            $staffProfile->skills = $request->skills;
            $staffProfile->status = 'pending';  // All new profiles start with pending status

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('staff-profiles', 'public');
                $staffProfile->profile_image = $path;
            }

            $staffProfile->save();

            return response()->json([
                'message' => 'Staff profile created successfully and pending approval',
                'data' => $staffProfile
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create staff profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create staff profile',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Display the specified staff profile.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is a service provider directly from the users table
            if ($user->user_type !== 'provider') {
                return response()->json([
                    'message' => 'You are not registered as a service provider',
                    'errors' => ['provider' => ['User is not a service provider']]
                ], 403);
            }

            $staffProfile = StaffProfile::where('id', $id)
                                       ->where('provider_id', $provider->id)
                                       ->first();

            if (!$staffProfile) {
                return response()->json([
                    'message' => 'Staff profile not found',
                    'errors' => ['profile' => ['Staff profile not found or you don\'t have permission']]
                ], 404);
            }

            return response()->json([
                'message' => 'Staff profile retrieved successfully',
                'data' => $staffProfile
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve staff profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'profile_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve staff profile',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Update the specified staff profile.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is a service provider directly from the users table
            if ($user->user_type !== 'provider') {
                return response()->json([
                    'message' => 'You are not registered as a service provider',
                    'errors' => ['provider' => ['User is not a service provider']]
                ], 403);
            }

            $staffProfile = StaffProfile::where('id', $id)
                                       ->where('provider_id', $provider->id)
                                       ->first();

            if (!$staffProfile) {
                return response()->json([
                    'message' => 'Staff profile not found',
                    'errors' => ['profile' => ['Staff profile not found or you don\'t have permission']]
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'position' => 'sometimes|string|max:255',
                'bio' => 'sometimes|string',
                'skills' => 'sometimes|array',
                'skills.*' => 'string',
                'profile_image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if we're updating approved profile
            if ($staffProfile->status === 'approved' &&
                ($request->has('name') || $request->has('position') ||
                 $request->has('bio') || $request->has('skills') ||
                 $request->hasFile('profile_image'))) {
                // Set back to pending if core attributes are changed
                $staffProfile->status = 'pending';
            }

            if ($request->has('name')) {
                $staffProfile->name = $request->name;
            }

            if ($request->has('position')) {
                $staffProfile->position = $request->position;
            }

            if ($request->has('bio')) {
                $staffProfile->bio = $request->bio;
            }

            if ($request->has('skills')) {
                $staffProfile->skills = $request->skills;
            }

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($staffProfile->profile_image) {
                    Storage::disk('public')->delete($staffProfile->profile_image);
                }

                $path = $request->file('profile_image')->store('staff-profiles', 'public');
                $staffProfile->profile_image = $path;
            }

            $staffProfile->save();

            $message = $staffProfile->status === 'pending'
                ? 'Staff profile updated successfully and pending approval'
                : 'Staff profile updated successfully';

            return response()->json([
                'message' => $message,
                'data' => $staffProfile
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update staff profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'profile_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to update staff profile',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Remove the specified staff profile.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is a service provider directly from the users table
            if ($user->user_type !== 'provider') {
                return response()->json([
                    'message' => 'You are not registered as a service provider',
                    'errors' => ['provider' => ['User is not a service provider']]
                ], 403);
            }

            $staffProfile = StaffProfile::where('id', $id)
                                       ->where('provider_id', $provider->id)
                                       ->first();

            if (!$staffProfile) {
                return response()->json([
                    'message' => 'Staff profile not found',
                    'errors' => ['profile' => ['Staff profile not found or you don\'t have permission']]
                ], 404);
            }

            // Delete profile image if exists
            if ($staffProfile->profile_image) {
                Storage::disk('public')->delete($staffProfile->profile_image);
            }

            $staffProfile->delete();

            return response()->json([
                'message' => 'Staff profile deleted successfully'
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to delete staff profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'profile_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to delete staff profile',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }
}
