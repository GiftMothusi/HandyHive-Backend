<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\StaffProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AdminApprovalController extends Controller
{
    /**
     * Get pending services for approval.
     *
     * @return JsonResponse
     */
    public function getPendingServices(): JsonResponse
    {
        try {
            // Check if user is an admin
            if (Auth::user()->userType !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['admin' => ['Only administrators can perform this action']]
                ], 403);
            }

            $pendingServices = ProviderService::with('provider.user')
                                            ->where('status', 'pending')
                                            ->get();

            return response()->json([
                'message' => 'Pending services retrieved successfully',
                'data' => $pendingServices
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve pending services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve pending services',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Get pending staff profiles for approval.
     *
     * @return JsonResponse
     */
    public function getPendingStaffProfiles(): JsonResponse
    {
        try {
            // Check if user is an admin
            if (Auth::user()->userType !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['admin' => ['Only administrators can perform this action']]
                ], 403);
            }

            $pendingProfiles = StaffProfile::with('provider.user')
                                          ->where('status', 'pending')
                                          ->get();

            return response()->json([
                'message' => 'Pending staff profiles retrieved successfully',
                'data' => $pendingProfiles
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve pending staff profiles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve pending staff profiles',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Approve or reject a service.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function approveService(Request $request, string $id): JsonResponse
    {
        try {
            // Check if user is an admin
            if (Auth::user()->userType !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['admin' => ['Only administrators can perform this action']]
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,rejected',
                'rejection_reason' => 'required_if:status,rejected|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = ProviderService::findOrFail($id);

            $service->status = $request->status;

            if ($request->status === 'rejected' && $request->has('rejection_reason')) {
                $service->rejection_reason = $request->rejection_reason;
            } else {
                $service->rejection_reason = null;
            }

            $service->save();

            $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';

            return response()->json([
                'message' => "Service {$statusMessage} successfully",
                'data' => $service
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to approve/reject service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to process service approval',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Approve or reject a staff profile.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function approveStaffProfile(Request $request, string $id): JsonResponse
    {
        try {
            // Check if user is an admin
            if (Auth::user()->userType !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['admin' => ['Only administrators can perform this action']]
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,rejected',
                'rejection_reason' => 'required_if:status,rejected|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $staffProfile = StaffProfile::findOrFail($id);

            $staffProfile->status = $request->status;

            if ($request->status === 'rejected' && $request->has('rejection_reason')) {
                $staffProfile->rejection_reason = $request->rejection_reason;
            } else {
                $staffProfile->rejection_reason = null;
            }

            $staffProfile->save();

            $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';

            return response()->json([
                'message' => "Staff profile {$statusMessage} successfully",
                'data' => $staffProfile
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to approve/reject staff profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'profile_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to process staff profile approval',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }
}
