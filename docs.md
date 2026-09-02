# Backend Build Spec — Fleet Management / Bus Booking System

> **How to use this file:** Feed this whole document to an AI coding agent
> (Claude Code, etc.) as the spec for generating the **Laravel backend**. It
> contains the business requirements, the exact database schema already
> decided on, and the API contract to implement. Treat the schema in Section
> 2 as fixed/authoritative — do not redesign it, only build migrations,
> models, and logic that match it exactly.

## 1. What to Build

A Laravel (latest stable) REST API for a bus booking system operating between
Egyptian cities (Cairo, Giza, Al Fayyum, Al Minya, Asyut, ...). Trips run a
bus along an ordered route of stations. Users book a **seat for a segment**
of the route (not necessarily the whole trip), and the same seat can be
booked multiple times on one trip as long as the booked segments don't
overlap.

**Example:** Route Cairo → Al Fayyum → Al Minya → Asyut. If seat #5 is booked
Cairo → Al Minya, it cannot also be booked for Cairo→Al Fayyum, Al
Fayyum→Al Minya, or Al Fayyum→Asyut (all overlap). It **can** still be booked
for Al Minya → Asyut (no overlap).

This is a client-agnostic API — build it as a pure Laravel API (no Blade/
Inertia views); the frontend is a separate Next.js app consuming it over
HTTP/JSON with CORS enabled.

Keep it simple. Don't add features, tables, or columns beyond what's
specified below — this assessment explicitly penalizes over-engineering.

## 2. Database Schema (authoritative — build migrations to match exactly)

This schema is already finalized. Below are the tables as they must exist,
derived directly from the project's SQL dump. All tables except `users` use
**soft deletes** (`deleted_at`) — a booking is "cancelled" by soft-deleting
its row, not via a status column.

### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| name | varchar(255) not null | |
| email | varchar(255) not null | **unique** |
| phone | varchar(255) not null | **unique** |
| email_verified_at | timestamp nullable | |
| password | varchar(255) not null | |
| two_factor_secret / two_factor_recovery_codes / two_factor_confirmed_at | | (2FA columns exist; not required to implement 2FA logic for this assessment) |
| remember_token | varchar(100) nullable | |
| created_at, updated_at, deleted_at | timestamps | |

`bookings.user_id` is **not nullable** — so **authentication is required**,
not optional, for this build (every booking must belong to a real user; no
guest-checkout fields exist on `bookings`). Implement registration/login
(Laravel Sanctum is the recommended, lightweight choice for a token-based API
consumed by Next.js).

### `stations`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| name | varchar(255) not null | |
| created_at, updated_at, deleted_at | timestamps | |

### `buses`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| plate_number | varchar(255) not null | **unique** |
| seats_count | smallint unsigned not null | default 12 |
| created_at, updated_at, deleted_at | timestamps | |

### `seats`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| bus_id | bigint unsigned not null | FK → buses.id, cascade delete |
| seat_number | smallint unsigned not null | |
| created_at, updated_at, deleted_at | timestamps | |
| | | **unique (bus_id, seat_number)** |

### `trips`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| code | varchar(30) not null | **unique** |
| bus_id | bigint unsigned not null | FK → buses.id, cascade delete |
| date | date not null | |
| created_at, updated_at, deleted_at | timestamps | |

### `trip_stops`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| trip_id | bigint unsigned not null | FK → trips.id, cascade delete |
| station_id | bigint unsigned not null | FK → stations.id, cascade delete |
| sequence_order | smallint unsigned not null | route order, 1, 2, 3... |
| created_at, updated_at, deleted_at | timestamps | |
| | | **unique (trip_id, sequence_order)** |
| | | **unique (trip_id, station_id)** |
| | | index (trip_id, sequence_order) |

`sequence_order` is the field all overlap/ordering logic is built on — never
compare station IDs directly for "before/after" checks, always compare
`sequence_order`.

### `bookings`
| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | |
| trip_id | bigint unsigned not null | FK → trips.id, cascade delete |
| seat_id | bigint unsigned not null | FK → seats.id, cascade delete |
| user_id | bigint unsigned not null | FK → users.id, cascade delete |
| start_trip_stop_id | bigint unsigned not null | FK → trip_stops.id, cascade delete |
| end_trip_stop_id | bigint unsigned not null | FK → trip_stops.id, cascade delete |
| created_at, updated_at, deleted_at | timestamps | |
| | | index (trip_id, seat_id) |
| | | index (start_trip_stop_id, end_trip_stop_id) |

**Note:** there is no `status` or `price` column. "Active" booking = row
where `deleted_at IS NULL` (Eloquent's default soft-delete scope already
excludes cancelled bookings from normal queries — use this instead of adding
a status enum).

### Standard Laravel infra tables
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations`,
`sessions`, `password_reset_tokens`, `passkeys` — these are framework
defaults / already-present features (passkeys, 2FA scaffolding). Don't build
booking logic around them; leave them as-is.

## 3. Eloquent Models & Relationships to Implement

- `User hasMany Bookings`
- `Station hasMany TripStops`
- `Bus hasMany Seats`, `Bus hasMany Trips`
- `Seat belongsTo Bus`, `Seat hasMany Bookings`
- `Trip belongsTo Bus`, `Trip hasMany TripStops` (ordered by `sequence_order`), `Trip hasMany Bookings`
- `TripStop belongsTo Trip`, `TripStop belongsTo Station`
- `Booking belongsTo Trip`, `Booking belongsTo Seat`, `Booking belongsTo User`, `Booking belongsTo TripStop` (as `startStop`, via `start_trip_stop_id`), `Booking belongsTo TripStop` (as `endStop`, via `end_trip_stop_id`)

All models except `User` should use the `SoftDeletes` trait to match the
schema's `deleted_at` columns.

## 4. Core Business Logic: The Overlap Rule

Given a candidate booking for `seat_id` on `trip_id` spanning
`[newStartOrder, newEndOrder)` (inclusive-exclusive on `sequence_order`), it
conflicts with an existing **non-deleted** booking on the same seat/trip iff:

```
existing.start_order < newEndOrder AND newStartOrder < existing.end_order
```

Implement this as a dedicated, unit-testable service class, e.g.
`App\Services\SeatAvailabilityService`, with methods like:
- `availableSeats(Trip $trip, TripStop $start, TripStop $end): Collection<Seat>`
- `isSeatAvailable(Trip $trip, Seat $seat, TripStop $start, TripStop $end): bool`
- `assertNoConflict(...)` — throws a domain exception if a conflict is found (used inside the booking transaction).

Query pattern (join on `start_trip_stop_id`/`end_trip_stop_id` back to
`trip_stops.sequence_order`, filtering out soft-deleted bookings — Eloquent
does this automatically unless you call `withTrashed()`):

```php
$conflict = Booking::query()
    ->where('trip_id', $trip->id)
    ->where('seat_id', $seat->id)
    ->whereHas('startStop', fn ($q) => $q->where('sequence_order', '<', $newEndOrder))
    ->whereHas('endStop', fn ($q) => $q->where('sequence_order', '>', $newStartOrder))
    ->exists();
```

## 5. Concurrency & Data Integrity — Required Approach

Wrap booking creation in `DB::transaction()`. Inside the transaction:
1. `lockForUpdate()` the relevant bookings for that `seat_id` + `trip_id`
   before re-checking overlap (pessimistic locking — chosen because MySQL,
   the likely target here, has no native exclusion/range constraint the way
   Postgres does).
2. Re-run the overlap check from Section 4 **inside** the lock.
3. If clear, create the `Booking` row.
4. If a conflict is found (including one that appeared between the client's
   "available seats" call and this request), roll back and return **409
   Conflict** with a clear message.

This must be tested with a concurrency test that fires two near-simultaneous
booking requests for the same seat/overlapping segment and asserts exactly
one succeeds.

## 6. Required API Endpoints

All routes under `/api`. Auth via Sanctum tokens (`Authorization: Bearer
{token}`); unauthenticated requests to protected routes return 401.

| Method | Endpoint | Auth? | Purpose |
|---|---|---|---|
| POST | `/register` | no | Create a user account |
| POST | `/login` | no | Returns a Sanctum token |
| POST | `/logout` | yes | Revoke current token |
| GET | `/me` | yes | Current authenticated user |
| GET | `/stations` | no | List all stations |
| GET | `/trips` | no | List trips (id, code, date, bus, ordered stops) |
| GET | `/trips/{trip}` | no | Trip detail incl. ordered `trip_stops` + `station` |
| GET | `/trips/{trip}/available-seats?start_station_id=&end_station_id=` | no | **Required by spec.** See validation below. |
| POST | `/bookings` | yes | **Required by spec.** Creates a booking for the authenticated user. |
| GET | `/bookings` | yes | List the authenticated user's own bookings |
| GET | `/bookings/{booking}` | yes | Booking detail (must belong to the requesting user) |
| DELETE | `/bookings/{booking}` | yes | Cancel (soft-delete) a booking belonging to the requesting user |

### `GET /trips/{trip}/available-seats` — validation & response

Validate, in order:
1. `trip` exists → else 404.
2. `start_station_id` and `end_station_id` are both stations that appear in
   this trip's `trip_stops` → else 422 with a message naming which station
   isn't on the route.
3. The start station's `sequence_order` is strictly less than the end
   station's → else 422 ("start station must come before end station").

Response: all seats on the trip's bus, each with an `is_available` boolean
computed via `SeatAvailabilityService`:

```json
{
  "trip_id": 1,
  "start_station_id": 1,
  "end_station_id": 3,
  "seats": [
    { "seat_id": 1, "seat_number": 1, "is_available": true },
    { "seat_id": 5, "seat_number": 5, "is_available": false }
  ]
}
```

### `POST /bookings` — request & response

Request body:
```json
{
  "trip_id": 1,
  "seat_id": 5,
  "start_station_id": 1,
  "end_station_id": 3
}
```
`user_id` is taken from the authenticated request, never from the body.

Validate identically to the available-seats endpoint (trip exists, both
stations on route, start before end), plus that `seat_id` belongs to the
trip's bus. Then run the transactional overlap check from Section 5.

Responses:
- `201 Created` — the booking, with `trip`, `seat`, `startStop.station`,
  `endStop.station` eager-loaded.
- `409 Conflict` — `{ "message": "This seat is no longer available for the selected segment." }`
- `422 Unprocessable Entity` — validation errors (Laravel's standard shape).

## 7. Validation

Use Form Request classes (not inline controller validation):
- `AvailableSeatsRequest` — validates `start_station_id`, `end_station_id`
  exist in `stations`.
- `StoreBookingRequest` — validates `trip_id`, `seat_id`, `start_station_id`,
  `end_station_id` exist in their respective tables.

Route-order and "station belongs to this trip" checks are business rules,
not simple field validation — put them in `SeatAvailabilityService` (or a
small `TripRouteValidator`) and throw domain exceptions that an exception
handler maps to 422/404, not into the Form Request itself.

## 8. Seeders

Seeders for `stations`, `buses`, `seats`, `users`, `trips`, `trip_stops`, and
`bookings` already exist (see the companion seeder files) and demonstrate the
overlap rule out of the box — reuse them as-is; don't regenerate.

## 9. Automated Tests (minimum required coverage)

Using Pest or PHPUnit:
- `GET available-seats` returns correct availability for a known segment.
- Successful booking via `POST /bookings`.
- Overlapping booking attempt is rejected (409 or 422 per your final design —
  be consistent).
- Same seat successfully rebooked on a non-overlapping segment.
- Invalid station order rejected (end before start).
- Invalid trip/station combination rejected (station not on that trip's
  route).
- Concurrency test: two simultaneous conflicting booking requests → exactly
  one succeeds.
- Unauthenticated `POST /bookings` returns 401.

## 10. Explicitly Out of Scope (don't build these)

- No `status`/`price` columns on bookings — don't add them.
- No guest checkout — `user_id` is required, so there's no
  customer-name/email path on the booking itself.
- No admin panel, no payment integration, no email/SMS notifications.
- Don't touch `passkeys`/2FA columns/tables — they exist for framework
  parity, not for this assessment's scope.
