<?php

namespace App\Services;

use App\Exceptions\InvalidTripRouteException;
use App\Exceptions\SeatConflictException;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Database\Eloquent\Collection;

class SeatAvailabilityService
{
    public function availableSeats(Trip $trip, TripStop $start, TripStop $end): Collection
    {
        $this->guardStopsOnTrip($trip, $start, $end);

        return $trip->bus->seats->map(function (Seat $seat) use ($trip, $start, $end): Seat {
            $seat->is_available = $this->isSeatAvailable($trip, $seat, $start, $end);

            return $seat;
        });
    }

    public function isSeatAvailable(Trip $trip, Seat $seat, TripStop $start, TripStop $end): bool
    {
        return ! $this->conflictExists($trip, $seat, $start->sequence_order, $end->sequence_order);
    }

    public function assertNoConflict(Trip $trip, Seat $seat, TripStop $start, TripStop $end): void
    {
        Booking::query()
            ->where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->lockForUpdate()
            ->get();

        if ($this->conflictExists($trip, $seat, $start->sequence_order, $end->sequence_order)) {
            throw new SeatConflictException;
        }
    }

    public function guardStopsOnTrip(Trip $trip, TripStop $start, TripStop $end): void
    {
        if ($start->trip_id !== $trip->id) {
            throw new InvalidTripRouteException('The start station is not on this trip\'s route.');
        }

        if ($end->trip_id !== $trip->id) {
            throw new InvalidTripRouteException('The end station is not on this trip\'s route.');
        }

        if ($start->sequence_order >= $end->sequence_order) {
            throw new InvalidTripRouteException('The start station must come before the end station on this trip\'s route.');
        }
    }

    public function resolveTripStop(Trip $trip, int $stationId, string $label = 'station'): TripStop
    {
        $tripStop = TripStop::where('trip_id', $trip->id)
            ->where('station_id', $stationId)
            ->first();

        if ($tripStop === null) {
            throw new InvalidTripRouteException("The {$label} (station #{$stationId}) is not on this trip's route.");
        }

        return $tripStop;
    }

    private function conflictExists(Trip $trip, Seat $seat, int $newStartOrder, int $newEndOrder): bool
    {
        return Booking::query()
            ->where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->whereHas('startStop', fn ($q) => $q->where('sequence_order', '<', $newEndOrder))
            ->whereHas('endStop', fn ($q) => $q->where('sequence_order', '>', $newStartOrder))
            ->exists();
    }
}
