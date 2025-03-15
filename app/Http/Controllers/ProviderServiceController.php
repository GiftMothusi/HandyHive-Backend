<?php

namespace App\Http\Controllers;

use App\Models\ProviderService;
use App\Models\ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProviderServiceController extends Controller
{
    /**
     * Display a listing of the provider services.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is a service provider
            if ($user->user_type !== 'provider') {
                return response()->json([
                    'message' => 'You are not registered as a service provider',
                    'errors' => ['provider' => ['User is not a service provider']]
                ], 403);
            }

            // Get services directly using the user_id instead of provider_id
            $services = ProviderService::where('provider_id', $user->id)->get();

            return response()->json([
                'message' => 'Services retrieved successfully',
                'data' => $services
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve provider services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve services',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Store a newly created provider service.
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
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'availability' => 'required|array',
                'availability.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = new ProviderService();
            $service->provider_id = $user->id; // Use user_id directly
            $service->title = $request->title;
            $service->description = $request->description;
            $service->price = $request->price;
            $service->availability = $request->availability;
            $service->status = 'pending';  // All new services start with pending status
            $service->save();

            return response()->json([
                'message' => 'Service created successfully and pending approval',
                'data' => $service
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create provider service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create service',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Display the specified provider service.
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

            $service = ProviderService::where('id', $id)
                                      ->where('provider_id', $user->id)
                                      ->first();

            if (!$service) {
                return response()->json([
                    'message' => 'Service not found',
                    'errors' => ['service' => ['Service not found or you don\'t have permission']]
                ], 404);
            }

            return response()->json([
                'message' => 'Service retrieved successfully',
                'data' => $service
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve provider service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Update the specified provider service.
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

            $service = ProviderService::where('id', $id)
                                      ->where('provider_id', $user->id)
                                      ->first();

            if (!$service) {
                return response()->json([
                    'message' => 'Service not found',
                    'errors' => ['service' => ['Service not found or you don\'t have permission']]
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'availability' => 'sometimes|array',
                'availability.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if we're updating approved service
            if ($service->status === 'approved' &&
                ($request->has('title') || $request->has('description') ||
                 $request->has('price') || $request->has('availability'))) {
                // Set back to pending if core attributes are changed
                $service->status = 'pending';
            }

            if ($request->has('title')) {
                $service->title = $request->title;
            }

            if ($request->has('description')) {
                $service->description = $request->description;
            }

            if ($request->has('price')) {
                $service->price = $request->price;
            }

            if ($request->has('availability')) {
                $service->availability = $request->availability;
            }

            $service->save();

            $message = $service->status === 'pending'
                ? 'Service updated successfully and pending approval'
                : 'Service updated successfully';

            return response()->json([
                'message' => $message,
                'data' => $service
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update provider service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to update service',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Remove the specified provider service.
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

            $service = ProviderService::where('id', $id)
                                      ->where('provider_id', $user->id)
                                      ->first();

            if (!$service) {
                return response()->json([
                    'message' => 'Service not found',
                    'errors' => ['service' => ['Service not found or you don\'t have permission']]
                ], 404);
            }

            $service->delete();

            return response()->json([
                'message' => 'Service deleted successfully'
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to delete provider service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to delete service',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }
}
