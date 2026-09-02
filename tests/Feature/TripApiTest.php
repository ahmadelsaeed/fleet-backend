<?php

use App\Models\Booking;
use App\Models\Bus;
use App\Models\Seat;
use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;

// ── Helper to build a minimal trip fixture ─────────────────────────────────

function createTripWithRoute(array $stationNames): array
{
    $bus = Bus::factory()->create(['seats_count' => 3]);

    // Create seats with sequential numbers to avoid (bus_id, seat_number) conflicts
    $seats = collect(range(1, 3))->map(
        fn ($n) => Seat::factory()->create(['bus_id' => $bus->id, 'seat_number' => $n])
    );

    $trip = Trip::factory()->create(['bus_id' => $bus->id]);

    $stations = collect($stationNames)->mapWithKeys(function ($name, $index) {
        return [$index => Station::factory()->create(['name' => $name])];
    });

    $stops = $stations->map(function ($station, $index) use ($trip) {
        return TripStop::factory()->create([
            'trip_id'        => $trip->id,
            'station_id'     => $station->id,
            'sequence_order' => $index + 1,
        ]);
    });

    return compact('bus', 'seats', 'trip', 'stations', 'stops');
}

// ── GET /trips ──────────────────────────────────────────────────────────────

it('lists all trips with stops and bus', function () {
    createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $response = $this->getJson('/api/trips');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'trips' => [
                    '*' => ['id', 'code', 'date', 'bus', 'trip_stops'],
                ],
            ],
        ]);
});

// ── GET /trips/{trip} ───────────────────────────────────────────────────────

it('shows a single trip', function () {
    ['trip' => $trip] = createTripWithRoute(['Cairo', 'Al Minya']);

    $this->getJson("/api/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('data.trip.id', $trip->id);
});

it('returns 404 for unknown trip', function () {
    $this->getJson('/api/trips/9999')->assertNotFound();
});

// ── GET /trips/{trip}/available-seats ───────────────────────────────────────

it('returns seat availability for a valid segment', function () {
    ['trip' => $trip, 'stations' => $stations] = createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $start = $stations->first();
    $end   = $stations->last();

    $response = $this->getJson("/api/trips/{$trip->id}/available-seats?start_station_id={$start->id}&end_station_id={$end->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'trip_id',
                'start_station_id',
                'end_station_id',
                'seats' => [
                    '*' => ['seat_id', 'seat_number', 'is_available'],
                ],
            ],
        ]);
});

it('rejects reversed station order with 422', function () {
    ['trip' => $trip, 'stations' => $stations] = createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $start = $stations->last();   // Asyut — later in route
    $end   = $stations->first();  // Cairo  — earlier in route

    $this->getJson("/api/trips/{$trip->id}/available-seats?start_station_id={$start->id}&end_station_id={$end->id}")
        ->assertUnprocessable();
});

it('rejects a station not on the trip route with 422', function () {
    ['trip' => $trip, 'stations' => $stations] = createTripWithRoute(['Cairo', 'Asyut']);
    $foreign = Station::factory()->create(['name' => 'Giza']);

    $this->getJson("/api/trips/{$trip->id}/available-seats?start_station_id={$foreign->id}&end_station_id={$stations->last()->id}")
        ->assertUnprocessable();
});

it('marks a seat as unavailable after a conflicting booking is made', function () {
    ['trip' => $trip, 'stations' => $stations, 'stops' => $stops, 'bus' => $bus, 'seats' => $seats] = createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $seat = $seats->first();
    $user = User::factory()->create();

    // Book Cairo -> Asyut (full route) for seat 1
    Booking::create([
        'trip_id'            => $trip->id,
        'seat_id'            => $seat->id,
        'user_id'            => $user->id,
        'start_trip_stop_id' => $stops->first()->id,
        'end_trip_stop_id'   => $stops->last()->id,
    ]);

    // Now query availability for Cairo -> Al Fayyum
    $start = $stations->first();
    $end   = $stations->get(1);

    $response = $this->getJson("/api/trips/{$trip->id}/available-seats?start_station_id={$start->id}&end_station_id={$end->id}");

    $response->assertOk();

    $seatData = collect($response->json('data.seats'))->firstWhere('seat_id', $seat->id);
    expect($seatData['is_available'])->toBeFalse();
});