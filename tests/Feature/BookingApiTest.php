<?php

use App\Models\Booking;
use App\Models\Bus;
use App\Models\Seat;
use App\Models\Station;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ── Test fixture helper ──────────────────────────────────────────────────────

function buildScenario(): array
{
    $bus = Bus::factory()->create(['seats_count' => 3]);

    $seats = collect(range(1, 3))->map(
        fn ($n) => Seat::factory()->create(['bus_id' => $bus->id, 'seat_number' => $n])
    );

    $trip = Trip::factory()->create(['bus_id' => $bus->id]);

    $stationNames = ['Cairo', 'Al Fayyum', 'Al Minya', 'Asyut'];
    $stations = collect($stationNames)->map(
        fn ($name) => Station::factory()->create(['name' => $name])
    );

    $stops = $stations->map(function ($station, $index) use ($trip) {
        return TripStop::factory()->create([
            'trip_id'        => $trip->id,
            'station_id'     => $station->id,
            'sequence_order' => $index + 1,
        ]);
    });

    return compact('bus', 'seats', 'trip', 'stations', 'stops');
}

// ── POST /bookings ─────────────────────────────────────────────────────────

it('creates a booking successfully', function () {
    $user = User::factory()->create();
    [
        'trip'     => $trip,
        'seats'    => $seats,
        'stations' => $stations,
    ] = buildScenario();

    $response = $this->actingAs($user)->postJson('/api/bookings', [
        'trip_id'          => $trip->id,
        'seat_id'          => $seats->first()->id,
        'start_station_id' => $stations->first()->id,
        'end_station_id'   => $stations->last()->id,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'booking' => ['id', 'trip', 'seat', 'start_stop', 'end_stop', 'created_at'],
            ],
        ]);

    $this->assertDatabaseHas('bookings', [
        'trip_id' => $trip->id,
        'seat_id' => $seats->first()->id,
        'user_id' => $user->id,
    ]);
});

it('returns 401 for unauthenticated booking attempt', function () {
    $this->postJson('/api/bookings', [])->assertUnauthorized();
});

it('rejects an overlapping booking with 409', function () {
    $user = User::factory()->create();
    [
        'trip'  => $trip,
        'seats' => $seats,
        'stops' => $stops,
    ] = buildScenario();

    $seat = $seats->first();

    // First booking: Cairo (stop 1) → Asyut (stop 4)
    Booking::create([
        'trip_id'            => $trip->id,
        'seat_id'            => $seat->id,
        'user_id'            => $user->id,
        'start_trip_stop_id' => $stops->get(0)->id,
        'end_trip_stop_id'   => $stops->get(3)->id,
    ]);

    // Attempt overlapping booking: Cairo → Al Fayyum (overlaps above)
    $stations = $trip->tripStops()->with('station')->get()->pluck('station');

    $response = $this->actingAs($user)->postJson('/api/bookings', [
        'trip_id'          => $trip->id,
        'seat_id'          => $seat->id,
        'start_station_id' => $stops->get(0)->station_id,
        'end_station_id'   => $stops->get(1)->station_id,
    ]);

    $response->assertStatus(409);
});

it('allows rebooking the same seat on a non-overlapping segment', function () {
    $user = User::factory()->create();
    [
        'trip'  => $trip,
        'seats' => $seats,
        'stops' => $stops,
    ] = buildScenario();

    $seat = $seats->first();

    // Book Cairo (stop 1) → Al Fayyum (stop 2)
    Booking::create([
        'trip_id'            => $trip->id,
        'seat_id'            => $seat->id,
        'user_id'            => $user->id,
        'start_trip_stop_id' => $stops->get(0)->id,
        'end_trip_stop_id'   => $stops->get(1)->id,
    ]);

    // Book Al Minya (stop 3) → Asyut (stop 4) — no overlap
    $response = $this->actingAs($user)->postJson('/api/bookings', [
        'trip_id'          => $trip->id,
        'seat_id'          => $seat->id,
        'start_station_id' => $stops->get(2)->station_id,
        'end_station_id'   => $stops->get(3)->station_id,
    ]);

    $response->assertCreated();
});

it('rejects end station before start station with 422', function () {
    $user = User::factory()->create();
    [
        'trip'  => $trip,
        'seats' => $seats,
        'stops' => $stops,
    ] = buildScenario();

    $response = $this->actingAs($user)->postJson('/api/bookings', [
        'trip_id'          => $trip->id,
        'seat_id'          => $seats->first()->id,
        'start_station_id' => $stops->get(2)->station_id, // Al Minya — later
        'end_station_id'   => $stops->get(0)->station_id, // Cairo    — earlier
    ]);

    $response->assertUnprocessable();
});

it('rejects a station not on the trip route with 422', function () {
    $user = User::factory()->create();
    [
        'trip'  => $trip,
        'seats' => $seats,
        'stops' => $stops,
    ] = buildScenario();

    $foreignStation = Station::factory()->create(['name' => 'Giza']);

    $response = $this->actingAs($user)->postJson('/api/bookings', [
        'trip_id'          => $trip->id,
        'seat_id'          => $seats->first()->id,
        'start_station_id' => $foreignStation->id,
        'end_station_id'   => $stops->get(1)->station_id,
    ]);

    $response->assertUnprocessable();
});

// ── GET /bookings ──────────────────────────────────────────────────────────

it('lists only the authenticated user bookings', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    [
        'trip'  => $trip,
        'seats' => $seats,
        'stops' => $stops,
    ] = buildScenario();

    Booking::create([
        'trip_id' => $trip->id, 'seat_id' => $seats->get(0)->id, 'user_id' => $user->id,
        'start_trip_stop_id' => $stops->get(0)->id, 'end_trip_stop_id' => $stops->get(1)->id,
    ]);
    Booking::create([
        'trip_id' => $trip->id, 'seat_id' => $seats->get(1)->id, 'user_id' => $other->id,
        'start_trip_stop_id' => $stops->get(0)->id, 'end_trip_stop_id' => $stops->get(1)->id,
    ]);

    $response = $this->actingAs($user)->getJson('/api/bookings');

    $response->assertOk();
    expect($response->json('data.bookings'))->toHaveCount(1);
});

// ── GET /bookings/{booking} ────────────────────────────────────────────────

it('shows own booking', function () {
    $user = User::factory()->create();
    ['trip' => $trip, 'seats' => $seats, 'stops' => $stops] = buildScenario();

    $booking = Booking::create([
        'trip_id' => $trip->id, 'seat_id' => $seats->first()->id, 'user_id' => $user->id,
        'start_trip_stop_id' => $stops->get(0)->id, 'end_trip_stop_id' => $stops->get(1)->id,
    ]);

    $this->actingAs($user)->getJson("/api/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.booking.id', $booking->id);
});

it('returns 403 when viewing another user booking', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    ['trip' => $trip, 'seats' => $seats, 'stops' => $stops] = buildScenario();

    $booking = Booking::create([
        'trip_id' => $trip->id, 'seat_id' => $seats->first()->id, 'user_id' => $other->id,
        'start_trip_stop_id' => $stops->get(0)->id, 'end_trip_stop_id' => $stops->get(1)->id,
    ]);

    $this->actingAs($user)->getJson("/api/bookings/{$booking->id}")
        ->assertForbidden();
});

// ── DELETE /bookings/{booking} ─────────────────────────────────────────────

it('cancels (soft-deletes) own booking', function () {
    $user = User::factory()->create();
    ['trip' => $trip, 'seats' => $seats, 'stops' => $stops] = buildScenario();

    $booking = Booking::create([
        'trip_id' => $trip->id, 'seat_id' => $seats->first()->id, 'user_id' => $user->id,
        'start_trip_stop_id' => $stops->get(0)->id, 'end_trip_stop_id' => $stops->get(1)->id,
    ]);

    $this->actingAs($user)->deleteJson("/api/bookings/{$booking->id}")
        ->assertOk();

    $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
});

it('returns 403 when cancelling another user booking', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    ['trip' => $trip, 'seats' => $seats, 'stops' => $stops] = buildScenario();

    $booking = Booking::create([
        'trip_id' => $trip->id, 'seat_id' => $seats->first()->id, 'user_id' => $other->id,
        'start_trip_stop_id' => $stops->get(0)->id, 'end_trip_stop_id' => $stops->get(1)->id,
    ]);

    $this->actingAs($user)->deleteJson("/api/bookings/{$booking->id}")
        ->assertForbidden();
});

// ── Concurrency test ───────────────────────────────────────────────────────

it('allows only one booking when two requests race for the same seat', function () {
    $user = User::factory()->create();
    [
        'trip'  => $trip,
        'seats' => $seats,
        'stops' => $stops,
    ] = buildScenario();

    $seat = $seats->first();

    $payload = [
        'trip_id'          => $trip->id,
        'seat_id'          => $seat->id,
        'start_station_id' => $stops->get(0)->station_id,
        'end_station_id'   => $stops->get(3)->station_id,
    ];

    // Simulate two concurrent requests sequentially (in-process, fastest approach)
    $firstResponse  = $this->actingAs($user)->postJson('/api/bookings', $payload);
    $secondResponse = $this->actingAs($user)->postJson('/api/bookings', $payload);

    $statuses = [$firstResponse->status(), $secondResponse->status()];
    sort($statuses);

    // Exactly one 201 and one 409
    expect($statuses)->toBe([201, 409]);

    // Exactly one row in the database
    expect(Booking::where('trip_id', $trip->id)->where('seat_id', $seat->id)->count())->toBe(1);
});