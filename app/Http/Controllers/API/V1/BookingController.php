<?php

namespace App\Http\Controllers\API\V1;

use App\Exceptions\InvalidTripRouteException;
use App\Exceptions\SeatConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use App\OpenApi\Operations\Bookings\BookingDestroy;
use App\OpenApi\Operations\Bookings\BookingShow;
use App\OpenApi\Operations\Bookings\BookingsIndex;
use App\OpenApi\Operations\Bookings\BookingStore;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(private readonly SeatAvailabilityService $availabilityService) {}

    /**
     * GET /bookings — list the authenticated user's bookings.
     */
    #[BookingsIndex]
    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['trip.bus', 'trip.tripStops.station', 'seat', 'startStop.station', 'endStop.station'])
            ->latest()
            ->get();

        return $this->respondWithSuccess(
            __('Bookings retrieved successfully'),
            ['bookings' => BookingResource::collection($bookings)]
        );
    }

    /**
     * POST /bookings — create a booking for the authenticated user.
     * Returns 409 if the seat is already taken for the requested segment.
     */
    #[BookingStore]
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $trip = Trip::findOrFail($validated['trip_id']);
        $seat = Seat::findOrFail($validated['seat_id']);

        if ($seat->bus_id !== $trip->bus_id) {
            return $this->respondWithError(
                __('The selected seat does not belong to this trip\'s bus.'),
                [],
                422
            );
        }

        try {
            $startStop = $this->availabilityService->resolveTripStop(
                $trip,
                (int) $validated['start_station_id'],
                'start station'
            );

            $endStop = $this->availabilityService->resolveTripStop(
                $trip,
                (int) $validated['end_station_id'],
                'end station'
            );

            $this->availabilityService->guardStopsOnTrip($trip, $startStop, $endStop);
        } catch (InvalidTripRouteException $e) {
            return $this->respondWithError($e->getMessage(), [], 422);
        }

        try {
            $booking = DB::transaction(function () use ($trip, $seat, $startStop, $endStop, $request): Booking {
                $this->availabilityService->assertNoConflict($trip, $seat, $startStop, $endStop);

                return Booking::create([
                    'trip_id' => $trip->id,
                    'seat_id' => $seat->id,
                    'user_id' => $request->user()->id,
                    'start_trip_stop_id' => $startStop->id,
                    'end_trip_stop_id' => $endStop->id,
                ]);
            });
        } catch (SeatConflictException $e) {
            return $this->respondWithError($e->getMessage(), [], 409);
        }

        $booking->load(['trip.bus', 'trip.tripStops.station', 'seat', 'startStop.station', 'endStop.station']);

        return $this->respondWithSuccess(
            __('Booking created successfully'),
            ['booking' => new BookingResource($booking)],
            201
        );
    }

    /**
     * GET /bookings/{booking} — show a single booking.
     * Returns 403 if the booking does not belong to the authenticated user.
     */
    #[BookingShow]
    public function show(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return $this->respondWithError(__('Forbidden.'), [], 403);
        }

        $booking->load(['trip.bus', 'trip.tripStops.station', 'seat', 'startStop.station', 'endStop.station']);

        return $this->respondWithSuccess(
            __('Booking retrieved successfully'),
            ['booking' => new BookingResource($booking)]
        );
    }

    /**
     * DELETE /bookings/{booking} — cancel (soft-delete) a booking.
     * Returns 403 if the booking does not belong to the authenticated user.
     */
    #[BookingDestroy]
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return $this->respondWithError(__('Forbidden.'), [], 403);
        }

        $booking->delete();

        return $this->respondWithSuccess(__('Booking cancelled successfully'));
    }
}
