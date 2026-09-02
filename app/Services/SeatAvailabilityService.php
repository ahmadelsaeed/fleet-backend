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
    /**
     * Return all seats on the trip's bus, each annotated with an is_available flag
     * for the given [start, end) segment.
     *
     * @return Collection<int, Seat>
     */
    public function availableSeats(Trip $trip, TripStop $start, TripStop $end): Collection
    {
        $this->guardStopsOnTrip($trip, $start, $end);

        return $trip->bus->seats->map(function (Seat $seat) use ($trip, $start, $end): Seat {
            $seat->is_available = $this->isSeatAvailable($trip, $seat, $start, $end);

            return $seat;
        });
    }

    /**
     * Return true when no active booking blocks this seat for [start, end).
     */
    public function isSeatAvailable(Trip $trip, Seat $seat, TripStop $start, TripStop $end): bool
    {
        return ! $this->conflictExists($trip, $seat, $start->sequence_order, $end->sequence_order);
    }

    /**
     * Assert no conflict inside a DB transaction + lockForUpdate() guard.
     *
     * @throws SeatConflictException
     */
    public function assertNoConflict(Trip $trip, Seat $seat, TripStop $start, TripStop $end): void
    {
        // Acquire a pessimistic lock on all existing bookings for this seat+trip
        // so concurrent requests cannot slip through between the read and the insert.
        Booking::query()
            ->where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->lockForUpdate()
            ->get();

        if ($this->conflictExists($trip, $seat, $start->sequence_order, $end->sequence_order)) {
            throw new SeatConflictException;
        }
    }

    /**
     * Validate that both stops belong to the trip and that start comes before end.
     *
     * @throws InvalidTripRouteException
     */
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

    /**
     * Resolve a station_id to its TripStop on the given trip.
     *
     * @throws InvalidTripRouteException
     */
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

    // ── private ───────────────────────────────────────────────────────────────

    /**
     * Core overlap check: existing.start < newEnd AND newStart < existing.end
     */
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
