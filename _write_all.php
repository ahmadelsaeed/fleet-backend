<?php

$base = __DIR__;

// ── Exceptions ──────────────────────────────────────────────────────────────

file_put_contents($base . '/app/Exceptions/SeatConflictException.php', <<<'PHP'
<?php

namespace App\Exceptions;

use RuntimeException;

class SeatConflictException extends RuntimeException
{
    public function __construct(string $message = 'This seat is no longer available for the selected segment.')
    {
        parent::__construct($message);
    }
}
PHP);

file_put_contents($base . '/app/Exceptions/InvalidTripRouteException.php', <<<'PHP'
<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidTripRouteException extends RuntimeException
{
    public function __construct(string $message = 'Invalid trip route.')
    {
        parent::__construct($message);
    }
}
PHP);

// ── Models (add SoftDeletes + relationships) ─────────────────────────────────

file_put_contents($base . '/app/Models/Station.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public function tripStops(): HasMany
    {
        return $this->hasMany(TripStop::class);
    }
}
PHP);

file_put_contents($base . '/app/Models/Bus.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use SoftDeletes;

    protected $fillable = ['plate_number', 'seats_count'];

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
PHP);

file_put_contents($base . '/app/Models/Seat.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seat extends Model
{
    use SoftDeletes;

    protected $fillable = ['bus_id', 'seat_number'];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
PHP);

file_put_contents($base . '/app/Models/Trip.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'bus_id', 'date'];

    protected $casts = ['date' => 'date'];

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function tripStops(): HasMany
    {
        return $this->hasMany(TripStop::class)->orderBy('sequence_order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
PHP);

file_put_contents($base . '/app/Models/TripStop.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripStop extends Model
{
    use SoftDeletes;

    protected $fillable = ['trip_id', 'station_id', 'sequence_order'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
PHP);

file_put_contents($base . '/app/Models/Booking.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'trip_id',
        'seat_id',
        'user_id',
        'start_trip_stop_id',
        'end_trip_stop_id',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The trip-stop where the passenger boards. */
    public function startStop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class, 'start_trip_stop_id');
    }

    /** The trip-stop where the passenger alights. */
    public function endStop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class, 'end_trip_stop_id');
    }
}
PHP);

// ── Service ──────────────────────────────────────────────────────────────────

file_put_contents($base . '/app/Services/SeatAvailabilityService.php', <<<'PHP'
<?php

namespace App\Services;

use App\Exceptions\InvalidTripRouteException;
use App\Exceptions\SeatConflictException;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripStop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SeatAvailabilityService
{
    /**
     * Return all seats on the trip's bus, each annotated with availability
     * for the given segment [start, end).
     *
     * @param  Trip     $trip
     * @param  TripStop $start
     * @param  TripStop $end
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
     * Assert no conflict exists, or throw SeatConflictException.
     * Must be called inside a DB::transaction() with a lockForUpdate() guard.
     */
    public function assertNoConflict(Trip $trip, Seat $seat, TripStop $start, TripStop $end): void
    {
        // Re-check inside the lock to prevent race conditions.
        Booking::query()
            ->where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->lockForUpdate()
            ->get(); // acquires the lock; result is discarded — overlap check below is authoritative

        if ($this->conflictExists($trip, $seat, $start->sequence_order, $end->sequence_order)) {
            throw new SeatConflictException();
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
     * Validate that a station (by its station_id) is on the given trip,
     * and return its TripStop record.
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
     * Core overlap query.
     * Two segments overlap iff: existing.start < newEnd AND newStart < existing.end
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
PHP);

// ── Resources ────────────────────────────────────────────────────────────────

file_put_contents($base . '/app/Http/Resources/StationResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
PHP);

file_put_contents($base . '/app/Http/Resources/TripStopResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sequence_order' => $this->sequence_order,
            'station'        => new StationResource($this->whenLoaded('station')),
        ];
    }
}
PHP);

file_put_contents($base . '/app/Http/Resources/TripResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'date'       => $this->date->toDateString(),
            'bus'        => [
                'id'          => $this->bus->id,
                'plate_number' => $this->bus->plate_number,
                'seats_count' => $this->bus->seats_count,
            ],
            'trip_stops' => TripStopResource::collection($this->whenLoaded('tripStops')),
        ];
    }
}
PHP);

file_put_contents($base . '/app/Http/Resources/BookingResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'trip'       => new TripResource($this->whenLoaded('trip')),
            'seat'       => [
                'id'          => $this->seat->id ?? null,
                'seat_number' => $this->seat->seat_number ?? null,
            ],
            'start_stop' => $this->when($this->relationLoaded('startStop'), fn () => [
                'id'             => $this->startStop->id,
                'sequence_order' => $this->startStop->sequence_order,
                'station'        => new StationResource($this->startStop->station),
            ]),
            'end_stop'   => $this->when($this->relationLoaded('endStop'), fn () => [
                'id'             => $this->endStop->id,
                'sequence_order' => $this->endStop->sequence_order,
                'station'        => new StationResource($this->endStop->station),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
PHP);

file_put_contents($base . '/app/Http/Resources/SeatAvailabilityResource.php', <<<'PHP'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a Seat model that has had `is_available` set on it by SeatAvailabilityService.
 */
class SeatAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'seat_id'      => $this->id,
            'seat_number'  => $this->seat_number,
            'is_available' => (bool) $this->is_available,
        ];
    }
}
PHP);

// ── Form Requests ────────────────────────────────────────────────────────────

file_put_contents($base . '/app/Http/Requests/AvailableSeatsRequest.php', <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the query-string parameters for GET /trips/{trip}/available-seats.
 *
 * Business-rule checks (stations on route, start before end) are handled in
 * SeatAvailabilityService so they can be reused by other callers.
 */
class AvailableSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_station_id' => ['required', 'integer', 'exists:stations,id'],
            'end_station_id'   => ['required', 'integer', 'exists:stations,id'],
        ];
    }
}
PHP);

file_put_contents($base . '/app/Http/Requests/StoreBookingRequest.php', <<<'PHP'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body for POST /bookings.
 *
 * Business-rule checks (stations on route, start before end, seat on bus) are
 * handled in SeatAvailabilityService.
 */
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id'          => ['required', 'integer', 'exists:trips,id'],
            'seat_id'          => ['required', 'integer', 'exists:seats,id'],
            'start_station_id' => ['required', 'integer', 'exists:stations,id'],
            'end_station_id'   => ['required', 'integer', 'exists:stations,id'],
        ];
    }
}
PHP);

// ── Controllers ───────────────────────────────────────────────────────────────

file_put_contents($base . '/app/Http/Controllers/API/V1/StationController.php', <<<'PHP'
<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Http\JsonResponse;

/**
 * @group Stations
 *
 * Endpoints for browsing bus stations.
 */
class StationController extends Controller
{
    /**
     * List all stations.
     *
     * Returns the complete list of stations served by the network.
     * No authentication required.
     */
    public function index(): JsonResponse
    {
        $stations = Station::orderBy('name')->get();

        return $this->respondWithSuccess(
            __('Stations retrieved successfully'),
            ['stations' => StationResource::collection($stations)]
        );
    }
}
PHP);

file_put_contents($base . '/app/Http/Controllers/API/V1/TripController.php', <<<'PHP'
<?php

namespace App\Http\Controllers\API\V1;

use App\Exceptions\InvalidTripRouteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableSeatsRequest;
use App\Http\Resources\SeatAvailabilityResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;

/**
 * @group Trips
 *
 * Endpoints for browsing trips and checking seat availability.
 */
class TripController extends Controller
{
    public function __construct(private readonly SeatAvailabilityService $availabilityService) {}

    /**
     * List all trips.
     *
     * Returns all trips with their bus details and ordered stop list.
     * No authentication required.
     */
    public function index(): JsonResponse
    {
        $trips = Trip::with(['bus', 'tripStops.station'])->get();

        return $this->respondWithSuccess(
            __('Trips retrieved successfully'),
            ['trips' => TripResource::collection($trips)]
        );
    }

    /**
     * Show a single trip.
     *
     * Returns full trip details including the ordered trip_stops with their stations.
     * No authentication required.
     */
    public function show(Trip $trip): JsonResponse
    {
        $trip->load(['bus', 'tripStops.station']);

        return $this->respondWithSuccess(
            __('Trip retrieved successfully'),
            ['trip' => new TripResource($trip)]
        );
    }

    /**
     * List available seats for a segment.
     *
     * Returns every seat on the trip's bus with an `is_available` boolean for
     * the requested start → end segment. No authentication required.
     *
     * @queryParam start_station_id integer required The boarding station ID. Example: 1
     * @queryParam end_station_id   integer required The alighting station ID. Example: 3
     */
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
            'trip_id'          => $trip->id,
            'start_station_id' => (int) $request->validated('start_station_id'),
            'end_station_id'   => (int) $request->validated('end_station_id'),
            'seats'            => SeatAvailabilityResource::collection($seats),
        ]);
    }
}
PHP);

file_put_contents($base . '/app/Http/Controllers/API/V1/BookingController.php', <<<'PHP'
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
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Bookings
 *
 * Endpoints for managing seat bookings. All routes require authentication.
 */
class BookingController extends Controller
{
    public function __construct(private readonly SeatAvailabilityService $availabilityService) {}

    /**
     * List the authenticated user's bookings.
     */
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
     * Create a new booking.
     *
     * Runs the transactional overlap check and returns 409 if the seat is
     * already taken for the requested segment.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $trip = Trip::findOrFail($validated['trip_id']);
        $seat = Seat::findOrFail($validated['seat_id']);

        // Ensure the seat belongs to the trip's bus.
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
                    'trip_id'            => $trip->id,
                    'seat_id'            => $seat->id,
                    'user_id'            => $request->user()->id,
                    'start_trip_stop_id' => $startStop->id,
                    'end_trip_stop_id'   => $endStop->id,
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
     * Show a single booking.
     *
     * Returns 403 if the booking does not belong to the authenticated user.
     */
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
     * Cancel (soft-delete) a booking.
     *
     * Returns 403 if the booking does not belong to the authenticated user.
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return $this->respondWithError(__('Forbidden.'), [], 403);
        }

        $booking->delete();

        return $this->respondWithSuccess(__('Booking cancelled successfully'));
    }
}
PHP);

// ── Routes ────────────────────────────────────────────────────────────────────

file_put_contents($base . '/routes/api.php', <<<'PHP'
<?php

use App\Http\Controllers\API\V1\Auth\LoginController;
use App\Http\Controllers\API\V1\Auth\LogoutController;
use App\Http\Controllers\API\V1\Auth\RegisterController;
use App\Http\Controllers\API\V1\BookingController;
use App\Http\Controllers\API\V1\ProfileController;
use App\Http\Controllers\API\V1\StationController;
use App\Http\Controllers\API\V1\TripController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::post('register', RegisterController::class);
Route::post('login', LoginController::class);

// ── Public resources ──────────────────────────────────────────────────────────
Route::get('stations', [StationController::class, 'index']);

Route::prefix('trips')->group(function () {
    Route::get('/', [TripController::class, 'index']);
    Route::get('{trip}', [TripController::class, 'show']);
    Route::get('{trip}/available-seats', [TripController::class, 'availableSeats']);
});

// ── Authenticated ──────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', LogoutController::class);
    Route::get('me', [ProfileController::class, 'me']);

    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('{booking}', [BookingController::class, 'show']);
        Route::delete('{booking}', [BookingController::class, 'destroy']);
    });
});
PHP);

echo "All files written successfully.\n";