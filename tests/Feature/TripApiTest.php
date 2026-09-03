<?php

use App\Models\Booking;
use App\Models\Bus;
use App\Models\Seat;
use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;

function createTripWithRoute(array $stationNames): array
{
    $bus = Bus::factory()->create(['seats_count' => 3]);

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

it('lists all trips with stops and bus', function () {
    createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $response = $this->getJson('/api/v1/trips');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Trips retrieved successfully')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'date',
                    'bus' => ['id', 'plate_number', 'seats_count'],
                    'trip_stops' => [
                        '*' => ['station' => ['id', 'name'], 'sequence_order'],
                    ],
                ],
            ],
            'meta' => ['page', 'total_pages'],
        ]);

    $response->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.total_pages', 1);
});

it('shows a single trip', function () {
    ['trip' => $trip] = createTripWithRoute(['Cairo', 'Al Minya']);

    $this->getJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('data.trip.id', $trip->id);
});

it('returns 404 for unknown trip', function () {
    $this->getJson('/api/v1/trips/9999')->assertNotFound();
});

it('returns seat availability for a valid segment', function () {
    ['trip' => $trip, 'stations' => $stations] = createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $start = $stations->first();
    $end   = $stations->last();

    $response = $this->getJson("/api/v1/trips/{$trip->id}/available-seats?start_station_id={$start->id}&end_station_id={$end->id}");

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

    $start = $stations->last();
    $end   = $stations->first();

    $this->getJson("/api/v1/trips/{$trip->id}/available-seats?start_station_id={$start->id}&end_station_id={$end->id}")
        ->assertUnprocessable();
});

it('rejects a station not on the trip route with 422', function () {
    ['trip' => $trip, 'stations' => $stations] = createTripWithRoute(['Cairo', 'Asyut']);
    $foreign = Station::factory()->create(['name' => 'Giza']);

    $this->getJson("/api/v1/trips/{$trip->id}/available-seats?start_station_id={$foreign->id}&end_station_id={$stations->last()->id}")
        ->assertUnprocessable();
});

it('marks a seat as unavailable after a conflicting booking is made', function () {
    ['trip' => $trip, 'stations' => $stations, 'stops' => $stops, 'bus' => $bus, 'seats' => $seats] = createTripWithRoute(['Cairo', 'Al Fayyum', 'Asyut']);

    $seat = $seats->first();
    $user = User::factory()->create();

    Booking::create([
        'trip_id'            => $trip->id,
        'seat_id'            => $seat->id,
        'user_id'            => $user->id,
        'start_trip_stop_id' => $stops->first()->id,
        'end_trip_stop_id'   => $stops->last()->id,
    ]);

    $start = $stations->first();
    $end   = $stations->get(1);

    $response = $this->getJson("/api/v1/trips/{$trip->id}/available-seats?start_station_id={$start->id}&end_station_id={$end->id}");

    $response->assertOk();

    $seatData = collect($response->json('data.seats'))->firstWhere('seat_id', $seat->id);
    expect($seatData['is_available'])->toBeFalse();
});
