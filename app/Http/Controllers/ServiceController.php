<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Throwable;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Service::query();

            // Filter by category if provided
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $services = $query->get();

            return response()->json([
                'message' => 'Services retrieved successfully',
                'data' => $services
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve services', [
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
     * Display the specified service.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            return response()->json([
                'message' => 'Service retrieved successfully',
                'data' => $service
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service',
                'errors' => ['general' => ['Service not found or an error occurred']]
            ], 404);
        }
    }

    /**
     * Get service providers for a specific service.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function providers(string $id, Request $request): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            $query = ServiceProvider::where('category', $service->category)
                                    ->where('status', 'active');

            // Filter by rating if provided
            if ($request->has('min_rating')) {
                $query->where('rating', '>=', $request->min_rating);
            }

            $providers = $query->with('user')->get();

            // Transform the providers to include user data
            $transformedProviders = $providers->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->user->name ?? 'Unknown',
                    'category' => $provider->category,
                    'description' => $provider->description,
                    'hourlyRate' => $provider->hourly_rate,
                    'rating' => $provider->rating,
                    'availability' => $provider->availability,
                    'image' => $provider->profile_image,
                    'status' => $provider->status
                ];
            });

            return response()->json([
                'message' => 'Service providers retrieved successfully',
                'data' => $transformedProviders
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve service providers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service providers',
                'errors' => ['general' => ['Service not found or an error occurred']]
            ], 404);
        }
    }

    /**
     * Get availability for a service.
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function availability(string $id, Request $request): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            // Provider ID is required for availability
            $validator = Validator::make($request->all(), [
                'provider_id' => 'required|exists:service_providers,id',
                'date' => 'sometimes|date|after_or_equal:today',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $provider = ServiceProvider::findOrFail($request->provider_id);

            // Get the date to check availability for
            $date = $request->has('date')
                ? Carbon::parse($request->date)
                : Carbon::today();

            // In a real implementation, this would check against the provider's
            // schedule and existing bookings
            // For now, return mock data

            // Check if this day of week is in the provider's availability
            $dayOfWeek = strtolower($date->format('D'));
            $isAvailableDay = in_array($dayOfWeek, array_map('strtolower', $provider->availability));

            if (!$isAvailableDay) {
                return response()->json([
                    'message' => 'Provider not available on this day',
                    'data' => [
                        'available' => false,
                        'available_times' => []
                    ]
                ]);
            }

            // Mock available time slots
            $availableTimes = [
                '09:00:00' => '11:00:00',
                '12:00:00' => '14:00:00',
                '15:00:00' => '17:00:00',
            ];

            return response()->json([
                'message' => 'Availability retrieved successfully',
                'data' => [
                    'available' => true,
                    'available_times' => $availableTimes
                ]
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve service availability', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service availability',
                'errors' => ['general' => ['Service not found or an error occurred']]
            ], 404);
        }
    }

    /**
     * Get pricing for a service.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function pricing(string $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            // Format pricing information
            $pricingInfo = [
                'base_rate' => $service->base_rate,
                'minimum_hours' => $service->duration['minimum'] ?? 1,
                'maximum_hours' => $service->duration['maximum'] ?? 8,
                'premium_rates' => [
                    'weekends' => '15% extra',
                    'holidays' => '25% extra',
                    'last_minute' => '10% extra (less than 24 hours notice)',
                ],
                'discounts' => [
                    'early_bird' => '10% off (7+ days in advance)',
                    'bulk_booking' => '5% off (5+ hours)',
                ]
            ];

            return response()->json([
                'message' => 'Pricing information retrieved successfully',
                'data' => $pricingInfo
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve service pricing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service pricing',
                'errors' => ['general' => ['Service not found or an error occurred']]
            ], 404);
        }
    }

    /**
     * Calculate price for a service booking.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function calculatePrice(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = Service::findOrFail($id);

            // Calculate duration
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $durationInHours = $endTime->diffInMinutes($startTime) / 60;

            // Calculate base amount
            $baseAmount = $service->base_rate * $durationInHours;

            // Apply premium for weekend or holiday
            $premium = 0;
            if ($startTime->isWeekend()) {
                $premium = $baseAmount * 0.15; // 15% premium for weekends
            } elseif ($this->isPublicHoliday($startTime)) {
                $premium = $baseAmount * 0.25; // 25% premium for holidays
            }

            // Apply discount for advance booking
            $discount = 0;
            $now = Carbon::now();
            if ($startTime->diffInDays($now) >= 7) {
                $discount = $baseAmount * 0.10; // 10% discount for booking 7+ days in advance
            }

            // Calculate final amount
            $finalAmount = $baseAmount + $premium - $discount;

            // Format price breakdown
            $priceBreakdown = [
                'base_amount' => $baseAmount,
                'hours' => $durationInHours,
                'hourly_rate' => $service->base_rate,
                'premium' => $premium,
                'premium_reason' => $startTime->isWeekend() ? 'weekend' : ($this->isPublicHoliday($startTime) ? 'holiday' : null),
                'discount' => $discount,
                'discount_reason' => $startTime->diffInDays($now) >= 7 ? 'early_bird' : null,
                'final_amount' => $finalAmount,
                'currency' => 'ZAR',
                'commission' => $finalAmount * 0.15, // 15% platform commission
            ];

            return response()->json([
                'message' => 'Price calculated successfully',
                'data' => $priceBreakdown
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to calculate price', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Failed to calculate price',
                'errors' => ['general' => ['Service not found or an error occurred']]
            ], 404);
        }
    }

    /**
     * Check if a date is a public holiday in South Africa.
     *
     * @param Carbon $date
     * @return bool
     */
    private function isPublicHoliday(Carbon $date): bool
    {
        // South African public holidays for 2025 (simplified list)
        $holidays = [
            '2025-01-01', // New Year's Day
            '2025-03-21', // Human Rights Day
            '2025-04-18', // Good Friday
            '2025-04-21', // Family Day
            '2025-04-27', // Freedom Day
            '2025-05-01', // Workers' Day
            '2025-06-16', // Youth Day
            '2025-08-09', // National Women's Day
            '2025-09-24', // Heritage Day
            '2025-12-16', // Day of Reconciliation
            '2025-12-25', // Christmas Day
            '2025-12-26', // Day of Goodwill
        ];

        return in_array($date->format('Y-m-d'), $holidays);
    }
}
