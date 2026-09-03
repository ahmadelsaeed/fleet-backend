<?php

namespace App\Http\Controllers\API\V1;

use App\Exceptions\InvalidTripRouteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableSeatsRequest;
use App\Http\Resources\SeatAvailabilityResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\OpenApi\Operations\Trips\TripAvailableSeats;
use App\OpenApi\Operations\Trips\TripShow;
use App\OpenApi\Operations\Trips\TripsIndex;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    public function __construct(private readonly SeatAvailabilityService $availabilityService) {}

    #[TripsIndex]
    public function index(): JsonResponse
    {
        $trips = Trip::with(['bus', 'tripStops.station'])->whereDate('date', today())->paginate(10);

        return $this->respondWithSuccess(__('Trips retrieved successfully'), TripResource::collection($trips),200,true);
    }

    #[TripShow]
    public function show(Trip $trip): JsonResponse
    {
        $trip->load(['bus', 'tripStops.station']);

        return $this->respondWithSuccess(__('Trip retrieved successfully'), ['trip' => new TripResource($trip)]);
    }

    #[TripAvailableSeats]
    public function availableSeats(AvailableSeatsRequest $request, Trip $trip): JsonResponse
    {
        try {
            $startStop = $this->availabilityService->resolveTripStop(
                $trip,
                (int) $request->validated('start_station_id'),
                'start station'
            );

            $endStop = $this->availabilityService->resolveTripStop(
                $trip,
                (int) $request->validated('end_station_id'),
                'end station'
            );

            $this->availabilityService->guardStopsOnTrip($trip, $startStop, $endStop);

            $seats = $this->availabilityService->availableSeats($trip, $startStop, $endStop);
        } catch (InvalidTripRouteException $e) {
            return $this->respondWithError($e->getMessage(), [], 422);
        }

        return $this->respondWithSuccess(__('Seat availability retrieved successfully'), [
            'trip_id' => $trip->id,
            'start_station_id' => (int) $request->validated('start_station_id'),
            'end_station_id' => (int) $request->validated('end_station_id'),
            'seats' => SeatAvailabilityResource::collection($seats),
        ]);
    }
}
