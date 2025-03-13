<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Throwable;

class BookingController extends Controller
{
    /**
     * Display a listing of the bookings for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $status = $request->query('status');
            $query = Booking::where('client_id', $user->id);

            if ($status) {
                $query->where('status', $status);
            }

            $bookings = $query->with(['provider', 'service'])->orderBy('start_time', 'desc')->get();

            return response()->json([
                'message' => 'Bookings retrieved successfully',
                'data' => $bookings
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve bookings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve bookings',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Store a newly created booking in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider_id' => 'required|exists:service_providers,id',
                'service_id' => 'required|exists:services,id',
                'start_time' => 'required|date|after:now',
                'end_time' => 'required|date|after:start_time',
                'location' => 'required|string|max:255',
                'access_instructions' => 'nullable|string|max:1000',
                'special_instructions' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $provider = ServiceProvider::findOrFail($request->provider_id);
            $service = Service::findOrFail($request->service_id);

            // Calculate price
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $durationInHours = $endTime->diffInMinutes($startTime) / 60;
            $baseAmount = $service->base_rate * $durationInHours;

            // Apply premium for weekend or holiday
            $premium = 0;
            if ($startTime->isWeekend()) {
                $premium = $baseAmount * 0.15; // 15% premium for weekends
            }

            $finalAmount = $baseAmount + $premium;

            // Create booking
            $booking = new Booking();
            $booking->client_id = $user->id;
            $booking->provider_id = $request->provider_id;
            $booking->service_id = $request->service_id;
            $booking->status = 'pending';
            $booking->start_time = $request->start_time;
            $booking->end_time = $request->end_time;
            $booking->location = [
                'address' => $request->location,
                'access_instructions' => $request->access_instructions ?? null,
            ];
            $booking->requirements = [
                'special_instructions' => $request->special_instructions ?? null,
            ];
            $booking->price = [
                'base_amount' => $baseAmount,
                'premium' => $premium,
                'discount' => 0,
                'final_amount' => $finalAmount,
                'commission' => $finalAmount * 0.15, // 15% platform commission
            ];
            $booking->payment_status = 'pending';
            $booking->save();

            return response()->json([
                'message' => 'Booking created successfully',
                'data' => $booking->load(['provider', 'service'])
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to create booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create booking',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Display the specified booking.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $booking = Booking::with(['provider', 'service'])->findOrFail($id);

            // Check if the authenticated user is the client
            if ($booking->client_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['general' => ['You do not have permission to view this booking']]
                ], 403);
            }

            return response()->json([
                'message' => 'Booking retrieved successfully',
                'data' => $booking
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve booking',
                'errors' => ['general' => ['Booking not found or an error occurred']]
            ], 404);
        }
    }

    /**
     * Update the specified booking in storage.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $booking = Booking::findOrFail($id);

            // Check if the authenticated user is the client
            if ($booking->client_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['general' => ['You do not have permission to update this booking']]
                ], 403);
            }

            // Check if the booking can be updated (not completed or cancelled)
            if ($booking->status === 'completed' || $booking->status === 'cancelled') {
                return response()->json([
                    'message' => 'Booking cannot be updated',
                    'errors' => ['general' => ['Completed or cancelled bookings cannot be updated']]
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'start_time' => 'sometimes|date|after:now',
                'end_time' => 'sometimes|date|after:start_time',
                'location' => 'sometimes|string|max:255',
                'access_instructions' => 'nullable|string|max:1000',
                'special_instructions' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update booking fields
            if ($request->has('start_time')) {
                $booking->start_time = $request->start_time;
            }

            if ($request->has('end_time')) {
                $booking->end_time = $request->end_time;
            }

            if ($request->has('location')) {
                $location = $booking->location;
                $location['address'] = $request->location;
                $booking->location = $location;
            }

            if ($request->has('access_instructions')) {
                $location = $booking->location;
                $location['access_instructions'] = $request->access_instructions;
                $booking->location = $location;
            }

            if ($request->has('special_instructions')) {
                $requirements = $booking->requirements;
                $requirements['special_instructions'] = $request->special_instructions;
                $booking->requirements = $requirements;
            }

            // If times changed, recalculate price
            if ($request->has('start_time') || $request->has('end_time')) {
                $startTime = Carbon::parse($booking->start_time);
                $endTime = Carbon::parse($booking->end_time);
                $durationInHours = $endTime->diffInMinutes($startTime) / 60;

                $service = Service::findOrFail($booking->service_id);
                $baseAmount = $service->base_rate * $durationInHours;

                // Apply premium for weekend or holiday
                $premium = 0;
                if ($startTime->isWeekend()) {
                    $premium = $baseAmount * 0.15; // 15% premium for weekends
                }

                $finalAmount = $baseAmount + $premium;

                $booking->price = [
                    'base_amount' => $baseAmount,
                    'premium' => $premium,
                    'discount' => 0,
                    'final_amount' => $finalAmount,
                    'commission' => $finalAmount * 0.15, // 15% platform commission
                ];
            }

            $booking->save();

            return response()->json([
                'message' => 'Booking updated successfully',
                'data' => $booking->fresh(['provider', 'service'])
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to update booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to update booking',
                'errors' => ['general' => ['Booking not found or an error occurred']]
            ], 500);
        }
    }

    /**
     * Cancel the specified booking.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $booking = Booking::findOrFail($id);

            // Check if the authenticated user is the client
            if ($booking->client_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['general' => ['You do not have permission to cancel this booking']]
                ], 403);
            }

            // Check if the booking can be cancelled (not completed or already cancelled)
            if ($booking->status === 'completed' || $booking->status === 'cancelled') {
                return response()->json([
                    'message' => 'Booking cannot be cancelled',
                    'errors' => ['general' => ['Completed or already cancelled bookings cannot be cancelled']]
                ], 422);
            }

            // Cancel the booking
            $booking->status = 'cancelled';
            $booking->payment_status = 'refunded'; // Assuming refund process would be handled elsewhere
            $booking->save();

            return response()->json([
                'message' => 'Booking cancelled successfully',
                'data' => $booking->fresh(['provider', 'service'])
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to cancel booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to cancel booking',
                'errors' => ['general' => ['Booking not found or an error occurred']]
            ], 500);
        }
    }

    /**
     * Mark a booking as completed.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function complete(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $booking = Booking::findOrFail($id);

            // Check if the authenticated user is authorized
            // Normally only admins or providers should be able to mark as complete
            // But for demo purposes, we'll allow clients too
            if ($booking->client_id !== $user->id && $booking->provider_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['general' => ['You do not have permission to complete this booking']]
                ], 403);
            }

            // Check if the booking can be completed (must be confirmed)
            if ($booking->status !== 'confirmed' && $booking->status !== 'in_progress') {
                return response()->json([
                    'message' => 'Booking cannot be completed',
                    'errors' => ['general' => ['Only confirmed or in-progress bookings can be marked as completed']]
                ], 422);
            }

            // Mark as completed
            $booking->status = 'completed';
            $booking->save();

            return response()->json([
                'message' => 'Booking marked as completed',
                'data' => $booking->fresh(['provider', 'service'])
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to complete booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to complete booking',
                'errors' => ['general' => ['Booking not found or an error occurred']]
            ], 500);
        }
    }

    /**
     * Rate and review a completed booking.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function rate(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000',
                'categories.punctuality' => 'sometimes|integer|min:1|max:5',
                'categories.quality' => 'sometimes|integer|min:1|max:5',
                'categories.communication' => 'sometimes|integer|min:1|max:5',
                'categories.professionalism' => 'sometimes|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $booking = Booking::findOrFail($id);

            // Check if the authenticated user is the client
            if ($booking->client_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['general' => ['You do not have permission to rate this booking']]
                ], 403);
            }

            // Check if the booking is completed
            if ($booking->status !== 'completed') {
                return response()->json([
                    'message' => 'Booking cannot be rated',
                    'errors' => ['general' => ['Only completed bookings can be rated']]
                ], 422);
            }

            // Check if booking was already rated
            $existingReview = Review::where('booking_id', $id)->first();
            if ($existingReview) {
                return response()->json([
                    'message' => 'Booking already rated',
                    'errors' => ['general' => ['This booking has already been rated']]
                ], 422);
            }

            DB::beginTransaction();

            // Create the review
            $review = new Review();
            $review->booking_id = $booking->id;
            $review->rater_id = $user->id;
            $review->ratee_id = $booking->provider_id;
            $review->scores = [
                'punctuality' => $request->categories['punctuality'] ?? $request->rating,
                'quality' => $request->categories['quality'] ?? $request->rating,
                'communication' => $request->categories['communication'] ?? $request->rating,
                'professionalism' => $request->categories['professionalism'] ?? $request->rating,
            ];
            $review->average_score = $request->rating;
            $review->comment = $request->review;
            $review->save();

            // Update provider's average rating
            $provider = ServiceProvider::findOrFail($booking->provider_id);
            $providerReviews = Review::where('ratee_id', $provider->id)->get();
            $averageRating = $providerReviews->avg('average_score');
            $provider->rating = $averageRating;
            $provider->save();

            DB::commit();

            return response()->json([
                'message' => 'Review submitted successfully',
                'data' => $review
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Failed to rate booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to submit review',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    /**
     * Get tracking information for a booking.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function tracking(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $booking = Booking::findOrFail($id);

            // Check if the authenticated user is the client
            if ($booking->client_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized access',
                    'errors' => ['general' => ['You do not have permission to track this booking']]
                ], 403);
            }

            // Check if the booking is in progress or confirmed
            if ($booking->status !== 'in_progress' && $booking->status !== 'confirmed') {
                return response()->json([
                    'message' => 'Booking cannot be tracked',
                    'errors' => ['general' => ['Only in-progress or confirmed bookings can be tracked']]
                ], 422);
            }

            // In a real implementation, this would fetch real-time location data
            // For now, return mock data
            $trackingData = [
                'provider_location' => [
                    'latitude' => -26.2041, // Mock coordinates for Johannesburg
                    'longitude' => 28.0473,
                    'last_updated' => now()->format('Y-m-d H:i:s'),
                ],
                'estimated_arrival' => now()->addMinutes(15)->format('Y-m-d H:i:s'),
                'status_updates' => [
                    [
                        'timestamp' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
                        'status' => 'Provider has started the journey',
                    ],
                    [
                        'timestamp' => now()->subMinutes(15)->format('Y-m-d H:i:s'),
                        'status' => 'Provider is on the way',
                    ],
                ]
            ];

            return response()->json([
                'message' => 'Tracking information retrieved successfully',
                'data' => $trackingData
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tracking information', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve tracking information',
                'errors' => ['general' => ['Booking not found or an error occurred']]
            ], 500);
        }
    }
}
